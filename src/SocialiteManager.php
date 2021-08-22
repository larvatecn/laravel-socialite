<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Socialite;

use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Larva\Socialite\Providers\AbstractProvider;

/**
 * 社交供应商管理器
 * @author Tongle Xu <xutongle@gmail.com>
 */
class SocialiteManager extends Manager implements Contracts\Factory
{
    /**
     * Get a driver instance.
     *
     * @param string $driver
     * @return AbstractProvider
     */
    public function with(string $driver): AbstractProvider
    {
        return $this->driver($driver);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createAlipayDriver(): AbstractProvider
    {
        $config = $this->config->get('services.alipay');
        return $this->buildProvider(
            Providers\AlipayProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createBaiduDriver(): AbstractProvider
    {
        $config = $this->config->get('services.baidu');
        return $this->buildProvider(
            Providers\BaiduProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createBitbucketDriver(): AbstractProvider
    {
        $config = $this->config->get('services.bitbucket');
        return $this->buildProvider(
            Providers\BitbucketProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createFacebookDriver(): AbstractProvider
    {
        $config = $this->config->get('services.facebook');
        return $this->buildProvider(
            Providers\FacebookProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGithubDriver(): AbstractProvider
    {
        $config = $this->config->get('services.github');
        return $this->buildProvider(
            Providers\GithubProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGitlabDriver(): AbstractProvider
    {
        $config = $this->config->get('services.gitlab');
        return $this->buildProvider(
            Providers\GitlabProvider::class, $config
        )->setHost($config['host'] ?? null);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGoogleDriver(): AbstractProvider
    {
        $config = $this->config->get('services.google');
        return $this->buildProvider(
            Providers\GoogleProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createLarvaDriver(): AbstractProvider
    {
        $config = $this->config->get('services.larva');
        return $this->buildProvider(
            Providers\LarvaProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createLibrespeedDriver(): AbstractProvider
    {
        $config = $this->config->get('services.librespeed');
        return $this->buildProvider(
            Providers\LibreSpeedProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createLinkedinDriver(): AbstractProvider
    {
        $config = $this->config->get('services.linkedin');
        return $this->buildProvider(
            Providers\LinkedInProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createQqDriver(): AbstractProvider
    {
        $config = $this->config->get('services.qq');
        return $this->buildProvider(
            Providers\QQProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createWeiboDriver(): AbstractProvider
    {
        $config = $this->config->get('services.weibo');
        return $this->buildProvider(
            Providers\WeBoProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createWechatDriver(): AbstractProvider
    {
        $config = $this->config->get('services.wechat');
        return $this->buildProvider(
            Providers\WeChatProvider::class, $config
        );
    }

    /**
     * Build an OAuth 2 provider instance.
     *
     * @param string $provider
     * @param array $config
     * @return AbstractProvider
     */
    public function buildProvider(string $provider, array $config): AbstractProvider
    {
        return new $provider(
            $this->container->make('request'), $config['client_id'],
            $config['client_secret'], $this->formatRedirectUrl($config),
            Arr::get($config, 'guzzle', [])
        );
    }

    /**
     * Format the server configuration.
     *
     * @param array $config
     * @return array
     */
    public function formatConfig(array $config): array
    {
        return array_merge([
            'identifier' => $config['client_id'],
            'secret' => $config['client_secret'],
            'callback_uri' => $this->formatRedirectUrl($config),
        ], $config);
    }

    /**
     * Format the callback URL, resolving a relative URI if needed.
     *
     * @param array $config
     * @return string
     */
    protected function formatRedirectUrl(array $config): string
    {
        $redirect = value($config['redirect']);
        return Str::startsWith($redirect, '/')
            ? $this->container->make('url')->to($redirect)
            : $redirect;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function getDefaultDriver(): string
    {
        throw new InvalidArgumentException('No Socialite driver was specified.');
    }
}
