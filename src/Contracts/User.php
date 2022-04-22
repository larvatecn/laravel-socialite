<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 *
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 */

namespace Larva\Socialite\Contracts;

/**
 * 社交用户接口
 * @property \App\Models\User|null $user 用户
 * @author Tongle Xu <xutongle@gmail.com>
 */
interface User
{
    /**
     * 关联用户
     * @param \Illuminate\Foundation\Auth\User $user
     * @return bool
     */
    public function connect(\Illuminate\Foundation\Auth\User $user);

    /**
     * 解除用户关联
     * @return bool
     */
    public function disconnect();

    /**
     * Get the provider name for the user.
     *
     * @return string
     */
    public function getProviderName();

    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getId();

    /**
     * Get the union id for the user.
     *
     * @return string
     */
    public function getUnionId();

    /**
     * Get the nickname / username for the user.
     *
     * @return string
     */
    public function getNickname();

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getName();

    /**
     * Get the e-mail address of the user.
     *
     * @return string
     */
    public function getEmail();

    /**
     * Get the avatar / image URL for the user.
     *
     * @return string
     */
    public function getAvatar();
}
