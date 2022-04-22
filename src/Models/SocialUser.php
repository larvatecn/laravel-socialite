<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 *
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 */

namespace Larva\Socialite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
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
 * @method static Builder byOpenid($openid)
 * @method static Builder byUnionid($unionid)
 * @method static Builder byUserid($userid)
 * @method static Builder byProvider($provider)
 * @method static Builder byWechatOfficialAccount() 获取微信公众平台
 * @method static Builder byOpenidAndProvider($openid, $provider)
 * @method static Builder byUnionidAndProvider($unionid, $provider)
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class SocialUser extends Model implements User
{
    public const PROVIDER_LARVA = 'larva';
    public const PROVIDER_LIBRESPEED = 'librespeed';
    public const PROVIDER_WEIBO = 'weibo';
    public const PROVIDER_QQ = 'qq';
    public const PROVIDER_ALIPAY = 'alipay';
    public const PROVIDER_BAIDU = 'baidu';
    public const PROVIDER_WECHAT = 'wechat';
    public const PROVIDER_WECHAT_WEB = 'wechat_web';
    public const PROVIDER_WECHAT_MOBILE = 'wechat_mobile';
    public const PROVIDER_GITHUB = 'github';
    public const PROVIDER_FACEBOOK = 'facebook';
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_LINKEDIN = 'linkedin';
    public const PROVIDER_BITBUCKET = 'bitbucket';
    public const PROVIDER_GITLAB = 'gitlab';
    public const PROVIDER_DOUYIN = 'douyin';
    public const PROVIDER_OUTLOOK = 'outlook';
    public const PROVIDER_TAOBAO = 'taobao';

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
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.' . config('auth.guards.web.provider') . '.model'));
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
     * @param Builder $query
     * @param string $openid
     * @return Builder
     */
    public function scopeByOpenid(Builder $query, string $openid): Builder
    {
        return $query->where('open_id', $openid);
    }

    /**
     * Finds an account by union_id.
     *
     * @param Builder $query
     * @param string $unionid
     * @return Builder
     */
    public function scopeByUnionid(Builder $query, string $unionid): Builder
    {
        return $query->where('union_id', $unionid);
    }

    /**
     * Finds an account by user_id.
     * @param Builder $query
     * @param int|string $userId
     * @return Builder
     */
    public function scopeByUserid(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Finds an account by provider.
     * @param Builder $query
     * @param string $provider
     * @return Builder
     */
    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Finds an account by wechat platform.
     * @param Builder $query
     * @return Builder
     */
    public function scopeByWechatOfficialAccount(Builder $query): Builder
    {
        return $query->where('provider', static::PROVIDER_WECHAT);
    }

    /**
     * Finds an account by open_id and provider.
     * @param Builder $query
     * @param string $openid
     * @param string $provider
     * @return Builder
     */
    public function scopeByOpenidAndProvider(Builder $query, string $openid, string $provider): Builder
    {
        return $query->where('open_id', $openid)->where('provider', $provider);
    }

    /**
     * Finds an account by union_id and provider.
     * @param Builder $query
     * @param string $unionid
     * @param string $provider
     * @return Builder
     */
    public function scopeByUnionidAndProvider(Builder $query, string $unionid, string $provider): Builder
    {
        return $query->where('union_id', $unionid)->where('provider', $provider);
    }

    /**
     * 获取所有提供商类型
     * @return array
     */
    public static function getProviders(): array
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
            } elseif (class_exists('\Larva\Passport\MiniProgram\MiniProgramUser')) {
                $miniProgramUser = \Larva\Passport\MiniProgram\MiniProgramUser::byUnionidAndProvider($user['union_id'], $user['provider'])->first();
                if ($miniProgramUser != null && $miniProgramUser->user_id) {
                    $user['user_id'] = $miniProgramUser->user_id;
                }
            }
        }
        return SocialUser::updateOrCreate([
            'open_id' => $user['open_id'], 'provider' => $user['provider']
        ], $user);
    }

    /**
     * 获取用户
     * @param array $user
     * @return SocialUser
     */
    public static function mapWechatMpUserToObject(array $user): SocialUser
    {
        return SocialUser::mapUserToObject([
            'provider' => SocialUser::PROVIDER_WECHAT,
            'open_id' => Arr::get($user, 'openid'),
            'union_id' => Arr::get($user, 'unionid'),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => Arr::get($user, 'nickname'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'headimgurl'),
            'data' => $user
        ]);
    }

    /**
     * 获取微信公众号 openid
     * @param string|int $userId
     * @return mixed
     */
    public static function getWechatMpOpenid($userId)
    {
        return SocialUser::byWechatOfficialAccount()->where('user_id', $userId)->value('open_id');
    }

    /**
     * 获取微信 Web openid
     * @param string|int $userId
     * @return mixed
     */
    public static function getWechatWebOpenid($userId)
    {
        return SocialUser::byProvider(static::PROVIDER_WECHAT_WEB)->where('user_id', $userId)->value('open_id');
    }

    /**
     * 获取微信手机 openid
     * @param string|int $userId
     * @return mixed
     */
    public static function getWechatMobileOpenid($userId)
    {
        return SocialUser::byProvider(static::PROVIDER_WECHAT_MOBILE)->where('user_id', $userId)->value('open_id');
    }

    /**
     * 获取用户名
     * @return string|null
     */
    public function getUsername(): ?string
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
    public function getOpenId(): string
    {
        return $this->open_id;
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getUnionId(): ?string
    {
        return $this->union_id;
    }

    /**
     * @return string
     */
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }
}
