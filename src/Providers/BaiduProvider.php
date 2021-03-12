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
 * Class BaiduProvider
 * @author Tongle Xu <xutongle@gmail.com>
 */
class BaiduProvider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['basic'];

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getProviderName(): string
    {
        return SocialUser::PROVIDER_BAIDU;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase("https://openapi.baidu.com/oauth/2.0/authorize", $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://openapi.baidu.com/oauth/2.0/token';
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
        $response = $this->getHttpClient()->get('https://openapi.baidu.com/rest/2.0/passport/users/getInfo', [
            'query' => [
                'access_token' => $token
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
        return SocialUser::mapUserToObject([
            'provider' => $this->getProviderName(),
            'open_id' => Arr::get($user, 'openid'),
            'union_id' => Arr::get($user, 'userid'),
            'nickname' => Arr::get($user, 'username'),
            'avatar' => isset($user['portrait']) ? 'http://tb.himg.baidu.com/sys/portrait/item/' . $user['portrait'] : null,
            'name' => Arr::get($user, 'realname'),
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
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query' => $this->getTokenFields($code),
        ]);
        return json_decode($response->getBody(), true);
    }
}
