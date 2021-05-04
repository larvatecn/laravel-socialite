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
 * 微信网站扫码登录
 * @author Tongle Xu <xutongle@gmail.com>
 */
class WeChatProvider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['snsapi_login'];

    /**
     * @var string
     */
    protected $openId;

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_WECHAT_WEB;
    }

    /**
     * set Open Id.
     *
     * @param string $openId
     */
    public function setOpenId(string $openId)
    {
        $this->openId = $openId;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase("https://open.weixin.qq.com/connect/qrconnect", $state);
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $url
     * @param string $state
     * @return string
     */
    protected function buildAuthUrlFromBase(string $url, string $state): string
    {
        return parent::buildAuthUrlFromBase($url, $state) . '#wechat_redirect';
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     * @return array
     */
    protected function getCodeFields($state = null): array
    {
        $codeFields = parent::getCodeFields($state);
        $codeFields['appid'] = $this->clientId;
        unset($codeFields['client_id']);
        return $codeFields;
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserByToken(string $token): array
    {
        if (in_array('snsapi_base', $this->scopes)) {
            $user = ['openid' => $this->openId];
        } else {
            $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
                'query' => [
                    'access_token' => $token,
                    'openid' => $this->openId,
                    'lang' => 'zh_CN',
                ],
            ]);
            $user = json_decode($response->getBody(), true);
        }
        return $user;
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
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
            'open_id' => Arr::get($user, 'openid'),
            'union_id' => Arr::get($user, 'unionid'),
            'nickname' => Arr::get($user, 'nickname'),
            'avatar' => isset($user['headimgurl']) ? $user['headimgurl'] : null,
            'name' => null,
            'email' => null,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     * @return array
     */
    protected function getTokenFields(string $code): array
    {
        return [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
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
            'query' => $this->getTokenFields($code),
        ]);
        $responseBody = json_decode($response->getBody(), true);
        if (isset($responseBody['openid'])) {
            $this->setOpenId($responseBody['openid']);
        }
        return $responseBody;
    }
}