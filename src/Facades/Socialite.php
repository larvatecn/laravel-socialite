<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 *
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 */

namespace Larva\Socialite\Facades;

use Illuminate\Support\Facades\Facade;
use Larva\Socialite\Contracts\Factory;
use Larva\Socialite\Contracts\Provider;
use Larva\Socialite\SocialiteManager;

/**
 * @method static Provider driver(string $driver = null)
 * @see \Larva\Socialite\SocialiteManager
 * @mixin SocialiteManager
 */
class Socialite extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}
