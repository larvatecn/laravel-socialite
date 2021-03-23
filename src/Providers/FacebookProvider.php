<?php

namespace Larva\Socialite\Providers;

use Illuminate\Support\Arr;
use Larva\Socialite\Contracts\User;
use Larva\Socialite\Models\SocialUser;

class FacebookProvider extends AbstractProvider
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = 'v3.3';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['name', 'email', 'gender', 'verified', 'link'];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * Display the dialog in a popup view.
     *
     * @var bool
     */
    protected $popup = false;

    /**
     * Re-request a declined permission.
     *
     * @var bool
     */
    protected $reRequest = false;

    /**
     * The access token that was last used to retrieve a user.
     *
     * @var string|null
     */
    protected $lastToken;

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    protected function getName(): string
    {
        return SocialUser::PROVIDER_FACEBOOK;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlFromBase('https://www.facebook.com/' . $this->version . '/dialog/oauth', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return $this->graphUrl . '/' . $this->version . '/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse(string $code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $data = json_decode($response->getBody(), true);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByToken(string $token)
    {
        $this->lastToken = $token;

        $meUrl = $this->graphUrl . '/' . $this->version . '/me?access_token=' . $token . '&fields=' . implode(',', $this->fields);

        if (!empty($this->clientSecret)) {
            $appSecretProof = hash_hmac('sha256', $token, $this->clientSecret);

            $meUrl .= '&appsecret_proof=' . $appSecretProof;
        }

        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     * @param array $user
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @param int|null $expiresIn
     * @return \Larva\Socialite\Contracts\User|SocialUser
     */
    protected function mapUserToObject(array $user, $accessToken = null, $refreshToken = null, $expiresIn = null)
    {
        $avatarUrl = $this->graphUrl . '/' . $this->version . '/' . $user['id'] . '/picture';
        $user['avatar_original'] = $avatarUrl . '?width=1920';
        $user['profile_url'] = $user['link'] ?? null;
        return SocialUser::mapUserToObject([
            'provider' => $this->getName(),
            'open_id' => $user['id'],
            'nickname' => null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $avatarUrl . '?type=normal',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at' => \Illuminate\Support\Carbon::now()->addSeconds($expiresIn),
            'data' => $user
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);

        if ($this->popup) {
            $fields['display'] = 'popup';
        }

        if ($this->reRequest) {
            $fields['auth_type'] = 'rerequest';
        }

        return $fields;
    }

    /**
     * Set the user fields to request from Facebook.
     *
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function asPopup()
    {
        $this->popup = true;

        return $this;
    }

    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function reRequest()
    {
        $this->reRequest = true;

        return $this;
    }

    /**
     * Get the last access token used.
     *
     * @return string|null
     */
    public function lastToken()
    {
        return $this->lastToken;
    }

    /**
     * Specify which graph version should be used.
     *
     * @param string $version
     * @return $this
     */
    public function usingGraphVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }
}
