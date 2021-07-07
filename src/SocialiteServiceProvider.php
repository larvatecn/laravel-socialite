<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Socialite;

use Illuminate\Support\ServiceProvider;
use Larva\Socialite\Contracts\Factory;

/**
 * 服务提供者
 * @author Tongle Xu <xutongle@gmail.com>
 */
class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->publishes([
                __DIR__ . '/../resources/lang' => resource_path('lang'),
            ], 'socialite-lang');
        }
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'socialite');
    }

    /**
     * 是否延迟加载
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return true;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Factory::class, function ($app) {
            return new SocialiteManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [Factory::class];
    }
}
