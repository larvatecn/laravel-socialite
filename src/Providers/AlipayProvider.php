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
 * 支付宝
 * @author Tongle Xu <xutongle@gmail.com>
 */
class AlipayProvider extends AbstractProvider
{
    /**
     * 接口域名.
     *
     * @var string
     */
    protected $baseUrl = 'https://openauth.alipay.com';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['auth_user'];

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_ALIPAY;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase($this->baseUrl . 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm', $state);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     * @return array
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);
        $fields['app_id'] = $this->clientId;
        unset($fields['client_id'], $fields['response_type']);
        return $fields;
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return $this->baseUrl . '/gateway.do';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     * @return array
     */
    protected function getTokenFields(string $code): array
    {
        $fields = [];
        $fields['app_id'] = $this->clientId;
        $fields['method'] = 'alipay.system.oauth.token';
        $fields['charset'] = 'utf8';
        $fields['sign_type'] = 'RSA2';
        $fields['timestamp'] = date("Y-m-d H:i:s");
        $fields['version'] = '1.0';
        $fields['grant_type'] = 'authorization_code';
        $fields['code'] = $code;
        ksort($fields);
        openssl_sign(urldecode(http_build_query($fields)), $fields['sign'], $this->getPrivatekey(), "sha256");
        $fields['sign'] = base64_encode($fields['sign']);
        return $fields;
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
        $fields = [];
        $fields['app_id'] = $this->clientId;
        $fields['method'] = 'alipay.user.info.share';
        $fields['charset'] = 'utf8';
        $fields['sign_type'] = 'RSA2';
        $fields['timestamp'] = date("Y-m-d H:i:s");
        $fields['version'] = '1.0';
        $fields['auth_token'] = $token;
        ksort($fields);
        openssl_sign(urldecode(http_build_query($fields)), $fields['sign'], $this->getPrivatekey(), "sha256");
        $fields['sign'] = base64_encode($fields['sign']);
        $response = $this->getHttpClient()->request("GET", $this->baseUrl . "/gateway.do", ['query' => $fields]);
        $user = Arr::get(json_decode($response->getBody(), true), "alipay_user_info_share_response");
        return $user;
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
            'open_id' => Arr::get($user, 'user_id'),
            'nickname' => Arr::get($user, 'nick_name'),
            'name' => Arr::get($user, 'nick_name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'avatar'),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }

    /**
     * 获取私钥
     * @return string
     */
    protected function getPrivatekey()
    {
        if (is_file($this->clientSecret)) {
            $privateKey = openssl_pkey_get_private(
                \Illuminate\Support\Str::startsWith($this->clientSecret, 'file://') ? $this->clientSecret : 'file://' . $this->clientSecret
            );
        } else {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($this->clientSecret, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
            $privateKey = openssl_get_privatekey($privateKey);
        }
        return $privateKey;
    }

    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function getCode()
    {
        return $this->request->input('auth_code');
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
        $response = parent::getAccessTokenResponse($code);
        return Arr::get($response, 'alipay_system_oauth_token_response');
    }
}
