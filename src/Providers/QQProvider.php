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
 * QQ登录供应商
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class QQProvider extends AbstractProvider
{

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['get_user_info'];

    /**
     * @var string
     */
    private $openId;

    /**
     * User unionid.
     *
     * @var string
     */
    protected $unionId;

    /**
     * get token(openid) with unionid.
     *
     * @var bool
     */
    protected $withUnionId = false;

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_QQ;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://graph.qq.com/oauth2.0/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'https://graph.qq.com/oauth2.0/token';
    }

    /**
     * @param bool $value
     *
     * @return self
     */
    public function withUnionId($value = true)
    {
        $this->withUnionId = $value;
        return $this;
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
        $url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $token;
        $this->withUnionId && $url .= '&unionid=1';
        $response = $this->getHttpClient()->get($url);
        $me = json_decode($this->removeCallback($response->getBody()->getContents()), true);
        $this->openId = $me['openid'];
        $this->unionId = isset($me['unionid']) ? $me['unionid'] : '';
        $response = $this->getHttpClient()->get(
            "https://graph.qq.com/user/get_user_info?access_token=$token&openid={$this->openId}&oauth_consumer_key={$this->clientId}"
        );
        return json_decode($this->removeCallback($response->getBody()->getContents()), true);
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
            'open_id' => $this->openId,
            'union_id' => $this->unionId,
            'nickname' => Arr::get($user, 'nickname'),
            'name' => null,
            'email' => null,
            'avatar' => Arr::get($user, 'figureurl_qq_2'),
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
            'query' => $this->getTokenFields($code),
        ]);

        /*
         * Response content format is "access_token=FE04************************CCE2&expires_in=7776000&refresh_token=88E4************************BE14"
         * Not like "{'access_token':'FE04************************CCE2','expires_in':7776000,'refresh_token':'88E4************************BE14'}"
         * So it can't be decode by json_decode!
        */
        $content = $response->getBody()->getContents();
        parse_str($content, $result);

        return $result;
    }

    /**
     * @param mixed $response
     * @return string
     */
    protected function removeCallback($response): string
    {
        if (strpos($response, 'callback') !== false) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }
        return $response;
    }
}
