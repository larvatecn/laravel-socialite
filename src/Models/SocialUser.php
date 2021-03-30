<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace Larva\Socialite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Larva\Socialite\Contracts\User;

/**
 * 社交账户模型
 * @property int $id ID
 * @property int|null $user_id 用户ID
 * @property string $open_id 社交用户ID
 * @property string|null $union_id 联合ID
 * @property string|null $name 用户名
 * @property string|null $nickname 昵称
 * @property string|null $email 邮箱
 * @property string|null $avatar 头像
 * @property string $provider 供应商
 * @property string|null $access_token 帐户令牌
 * @property string|null $refresh_token 刷新令牌
 * @property array|null $data 附加数据
 * @property \App\Models\User|null $user 用户
 * @property Carbon|null $expired_at 令牌过期时间
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 *
 * @method static SocialUser|null find($id)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byOpenid($openid)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byUnionid($unionid)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byUserid($userid)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byProvider($provider)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byWechatOfficialAccount() 获取微信公众平台
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byOpenidAndProvider($openid, $provider)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialUser byUnionidAndProvider($unionid, $provider)
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class SocialUser extends Model implements User
{
    const PROVIDER_LARVA = 'larva';
    const PROVIDER_LIBRESPEED = 'librespeed';
    const PROVIDER_WEIBO = 'weibo';
    const PROVIDER_QQ = 'qq';
    const PROVIDER_ALIPAY = 'alipay';
    const PROVIDER_BAIDU = 'baidu';
    const PROVIDER_WECHAT = 'wechat';
    const PROVIDER_WECHAT_WEB = 'wechat_web';
    const PROVIDER_WECHAT_MOBILE = 'wechat_mobile';
    const PROVIDER_GITHUB = 'github';
    const PROVIDER_FACEBOOK = 'facebook';
    const PROVIDER_GOOGLE = 'google';
    const PROVIDER_LINKEDIN = 'linkedin';
    const PROVIDER_BITBUCKET = 'bitbucket';
    const PROVIDER_GITLAB = 'gitlab';
    const PROVIDER_DOUYIN = 'douyin';
    const PROVIDER_OUTLOOK = 'outlook';
    const PROVIDER_TAOBAO = 'taobao';

    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'social_users';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'open_id', 'union_id', 'provider', 'name', 'nickname', 'email', 'avatar', 'access_token', 'refresh_token', 'expired_at', 'data',
    ];

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'expired_at',
        'created_at',
        'updated_at'
    ];

    /**
     * 为数组 / JSON 序列化准备日期。
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * Get the user relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(
            config('auth.providers.' . config('auth.guards.web.provider') . '.model')
        );
    }

    /**
     * 链接用户
     * @param \Illuminate\Foundation\Auth\User $user
     * @return bool
     */
    public function connect(\Illuminate\Foundation\Auth\User $user): bool
    {
        $this->user_id = $user->getAuthIdentifier();
        return $this->saveQuietly();
    }

    /**
     * 解除用户连接
     * @return bool
     */
    public function disconnect(): bool
    {
        $this->user_id = null;
        return $this->saveQuietly();
    }

    /**
     * Finds an account by open_id.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $openid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOpenid($query, $openid)
    {
        return $query->where('open_id', $openid);
    }

    /**
     * Finds an account by union_id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $unionid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUnionid($query, $unionid)
    {
        return $query->where('union_id', $unionid);
    }

    /**
     * Finds an account by user_id.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param integer $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUserid($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Finds an account by provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Finds an account by wechat platform.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $openid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWechatOfficialAccount($query)
    {
        return $query->where('provider', static::PROVIDER_WECHAT);
    }

    /**
     * Finds an account by open_id and provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $openid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOpenidAndProvider($query, $openid, $provider)
    {
        return $query->where('open_id', $openid)->where('provider', $provider);
    }

    /**
     * Finds an account by union_id and provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $unionid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUnionidAndProvider($query, $unionid, $provider)
    {
        return $query->where('union_id', $unionid)->where('provider', $provider);
    }

    /**
     * 获取所有提供商类型
     * @return array
     */
    public static function getProviders()
    {
        return [
            static::PROVIDER_LARVA => trans('socialite.' . static::PROVIDER_LARVA),
            static::PROVIDER_LIBRESPEED => trans('socialite.' . static::PROVIDER_LIBRESPEED),
            static::PROVIDER_WECHAT => trans('socialite.' . static::PROVIDER_WECHAT),
            static::PROVIDER_WECHAT_WEB => trans('socialite.' . static::PROVIDER_WECHAT_WEB),
            static::PROVIDER_WECHAT_MOBILE => trans('socialite.' . static::PROVIDER_WECHAT_MOBILE),
            static::PROVIDER_WEIBO => trans('socialite.' . static::PROVIDER_WEIBO),
            static::PROVIDER_QQ => trans('socialite.' . static::PROVIDER_QQ),
            static::PROVIDER_BAIDU => trans('socialite.' . static::PROVIDER_BAIDU),
            static::PROVIDER_ALIPAY => trans('socialite.' . static::PROVIDER_ALIPAY),
            static::PROVIDER_GITHUB => trans('socialite.' . static::PROVIDER_GITHUB),
            static::PROVIDER_FACEBOOK => trans('socialite.' . static::PROVIDER_FACEBOOK),
            static::PROVIDER_GOOGLE => trans('socialite.' . static::PROVIDER_GOOGLE),
            static::PROVIDER_LINKEDIN => trans('socialite.' . static::PROVIDER_LINKEDIN),
            static::PROVIDER_BITBUCKET => trans('socialite.' . static::PROVIDER_BITBUCKET),
            static::PROVIDER_GITLAB => trans('socialite.' . static::PROVIDER_GITLAB),
            static::PROVIDER_DOUYIN => trans('socialite.' . static::PROVIDER_DOUYIN),
            static::PROVIDER_TAOBAO => trans('socialite.' . static::PROVIDER_TAOBAO),
            static::PROVIDER_OUTLOOK => trans('socialite.' . static::PROVIDER_OUTLOOK),
        ];
    }

    /**
     * 获取用户
     * @param array $user
     * @return SocialUser
     */
    public static function mapUserToObject(array $user): SocialUser
    {
        //存在联合ID
        if (isset($user['union_id']) && !empty($user['union_id'])) {
            /** @var SocialUser $unionUser */
            $unionUser = SocialUser::byUnionIdAndProvider($user['union_id'], $user['provider'])->first();
            if ($unionUser != null && $unionUser->user_id) {
                $user['user_id'] = $unionUser->user_id;
            }
        }
        return SocialUser::updateOrCreate([
            'open_id' => $user['open_id'], 'provider' => $user['provider']
        ], $user);
    }

    /**
     * 生成用户名
     * @return string|null
     */
    public function generateUsername()
    {
        if (!empty($this->name)) {
            return $this->name;
        } else {
            return $this->nickname;
        }
    }

    /**
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getUnionId()
    {
        return $this->union_id;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }
}
