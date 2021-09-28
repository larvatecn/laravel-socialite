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
 * 微博供应商
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class WeiBoProvider extends AbstractProvider
{
    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_WEIBO;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://api.weibo.com/oauth2/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.weibo.com/oauth2/access_token';
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
        $response = $this->getHttpClient()->get('https://api.weibo.com/2/users/show.json', [
            'query' => [
                'access_token' => $token,
                'uid' => $this->getUid($token),
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
            'provider' => $this->getName(),
            'open_id' => Arr::get($user, 'idstr'),
            'nickname' => Arr::get($user, 'screen_name'),
            'avatar' => Arr::get($user, 'avatar_hd'),
            'name' => Arr::get($user, 'name'),
            'email' => null,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessTokenResponse(string $code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'query' => $this->getTokenFields($code),
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param $token
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUid($token)
    {
        $response = $this->getHttpClient()->get('https://api.weibo.com/2/account/get_uid.json', [
            'query' => ['access_token' => $token],
        ]);
        return json_decode($response->getBody(), true)['uid'];
    }
}
