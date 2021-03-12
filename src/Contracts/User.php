<?php

namespace Larva\Socialite\Contracts;

/**
 * 社交用户接口
 * @author Tongle Xu <xutongle@gmail.com>
 */
interface User
{
    /**
     * Get the provider name for the user.
     *
     * @return string
     */
    public function getProviderName();

    /**
     * Get the unique openid for the user.
     *
     * @return string
     */
    public function getOpenId();

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
