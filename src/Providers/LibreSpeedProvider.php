<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace Larva\Socialite\Providers;

use Illuminate\Support\Arr;
use Larva\Socialite\Contracts\User;
use Larva\Socialite\Models\SocialUser;

/**
 * LibreSpeed Provider
 * @author Tongle Xu <xutongle@gmail.com>
 */
class LibreSpeedProvider extends AbstractProvider
{
    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getProviderName(): string
    {
        return SocialUser::PROVIDER_LIBRESPEED;
    }

    /**
     * Get the authentication URL for the provider.
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://www.librespeed.net/oauth/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://www.librespeed.net/oauth/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserByToken(string $token)
    {
        $response = $this->getHttpClient()->get('https://www.librespeed.net/api/v1/user/profile', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true);
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
        return SocialUser::mapUserToObject([
            'provider' => $this->getProviderName(),
            'open_id' => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'username'),
            'avatar' => Arr::get($user, 'avatar'),
            'name' => Arr::get($user, 'username'),
            'email' => Arr::get($user, 'email'),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }
}
