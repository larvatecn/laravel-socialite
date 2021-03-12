<?php

namespace Larva\Socialite\Providers;

use Larva\Socialite\Contracts\User;
use Larva\Socialite\Models\SocialUser;

class GitlabProvider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['read_user'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The Gitlab instance host.
     *
     * @var string
     */
    protected $host = 'https://gitlab.com';

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getProviderName():string
    {
        return SocialUser::PROVIDER_GITLAB;
    }

    /**
     * Set the Gitlab instance host.
     *
     * @param string|null $host
     * @return $this
     */
    public function setHost($host)
    {
        if (!empty($host)) {
            $this->host = rtrim($host, '/');
        }

        return $this;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state):string
    {
        return $this->buildAuthUrlFromBase($this->host . '/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl():string
    {
        return $this->host . '/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByToken(string $token)
    {
        $userUrl = $this->host . '/api/v3/user?access_token=' . $token;

        $response = $this->getHttpClient()->get($userUrl);

        $user = json_decode($response->getBody(), true);

        return $user;
    }

    /**
     * Map the raw user array to a Socialite User instance.
     * @param array $user
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @param int|null $expiresIn
     * @return \Larva\Socialite\Contracts\User|SocialUser
     */
    protected function mapUserToObject(array $user, $accessToken = null, $refreshToken = null, $expiresIn = null)
    {
        return SocialUser::mapUserToObject([
            'provider' => $this->getProviderName(),
            'open_id' => $user['id'],
            'nickname' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar_url'],
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }
}
