<?php

namespace Larva\Socialite\Providers;

use Illuminate\Support\Arr;
use Larva\Socialite\Contracts\User;
use Larva\Socialite\Models\SocialUser;

class GoogleProvider extends AbstractProvider
{
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_GOOGLE;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.google.com/o/oauth2/auth', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByToken(string $token)
    {
        $response = $this->getHttpClient()->get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'query' => [
                'prettyPrint' => 'false',
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     * @param array $user
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @param int|null $expiresIn
     * @return User|SocialUser
     */
    protected function mapUserToObject(array $user, $accessToken = null, $refreshToken = null, $expiresIn = null)
    {
        // Deprecated: Fields added to keep backwards compatibility in 4.0. These will be removed in 5.0
        $user['id'] = Arr::get($user, 'sub');
        $user['verified_email'] = Arr::get($user, 'email_verified');
        $user['link'] = Arr::get($user, 'profile');

        return SocialUser::mapUserToObject([
            'provider' => $this->getName(),
            'open_id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'picture'),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }
}
