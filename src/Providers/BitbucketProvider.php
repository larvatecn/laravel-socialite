<?php

namespace Larva\Socialite\Providers;

use Exception;
use Illuminate\Support\Arr;
use Larva\Socialite\Contracts\User;
use Larva\Socialite\Models\SocialUser;

class BitbucketProvider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_BITBUCKET;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://bitbucket.org/site/oauth2/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://bitbucket.org/site/oauth2/access_token';
    }

    /**
     * @param string $token
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserByToken(string $token)
    {
        $userUrl = 'https://api.bitbucket.org/2.0/user?access_token=' . $token;

        $response = $this->getHttpClient()->get($userUrl);

        $user = json_decode($response->getBody(), true);

        if (in_array('email', $this->scopes)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * @param string $token
     * @return string|null|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = 'https://api.bitbucket.org/2.0/user/emails?access_token=' . $token;

        try {
            $response = $this->getHttpClient()->get($emailsUrl);
        } catch (Exception $e) {
            return;
        }

        $emails = json_decode($response->getBody(), true);

        foreach ($emails['values'] as $email) {
            if ($email['type'] == 'email' && $email['is_primary'] && $email['is_confirmed']) {
                return $email['email'];
            }
        }
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
            'provider' => $this->getName(),
            'open_id' => $user['uuid'],
            'nickname' => $user['username'],
            'name' => Arr::get($user, 'display_name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'links.avatar.href'),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }

    /**
     * Get the access token for the given code.
     *
     * @param string $code
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true)['access_token'];
    }
}
