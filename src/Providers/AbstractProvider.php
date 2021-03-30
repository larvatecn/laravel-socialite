<?php

namespace Larva\Socialite\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larva\Socialite\Contracts\Provider as ProviderContract;

/**
 * 供应商基类
 */
abstract class AbstractProvider implements ProviderContract
{
    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The HTTP Client instance.
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * The type of the encoding in the query.
     *
     * @var int Can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738.
     */
    protected $encodingType = PHP_QUERY_RFC1738;

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * The custom Guzzle configuration options.
     *
     * @var array
     */
    protected $guzzle = [];

    /**
     * The cached user instance.
     *
     * @var \Larva\Socialite\Contracts\User|null
     */
    protected $user;

    /**
     * Create a new provider instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     * @param array $guzzle
     */
    public function __construct(Request $request, string $clientId, string $clientSecret, string $redirectUrl, $guzzle = [])
    {
        $this->guzzle = $guzzle;
        $this->request = $request;
        $this->clientId = $clientId;
        $this->redirectUrl = $redirectUrl;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Get the name for the provider.
     *
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    abstract protected function getAuthUrl(string $state): string;

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array
     */
    abstract protected function getUserByToken(string $token);

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @param int|null $expiresIn
     * @return \Larva\Socialite\Contracts\User
     */
    abstract protected function mapUserToObject(array $user, $accessToken = null, $refreshToken = null, $expiresIn = null);

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $state = null;

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * Build the authentication URL for the provider from the given base URL.
     *
     * @param string $url
     * @param string $state
     * @return string
     */
    protected function buildAuthUrlFromBase(string $url, string $state): string
    {
        return $url . '?' . http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     * @return array
     */
    protected function getCodeFields($state = null): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Format the given scopes.
     *
     * @param array $scopes
     * @param string $scopeSeparator
     * @return string
     */
    protected function formatScopes(array $scopes, string $scopeSeparator): string
    {
        return implode($scopeSeparator, $scopes);
    }

    /**
     *
     * @return \Larva\Socialite\Contracts\User
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());
        $accessToken = Arr::get($response, 'access_token');
        $refreshToken = Arr::get($response, 'refresh_token');
        $expiresIn = (int)Arr::get($response, 'expires_in', 7200);
        $this->user = $this->mapUserToObject($this->getUserByToken($accessToken), $accessToken, $refreshToken, $expiresIn);
        return $this->user;
    }

    /**
     * Get a Social User instance from a known access token.
     *
     * @param string $token
     * @return \Larva\Socialite\Contracts\User
     */
    public function userFromToken(string $token)
    {
        return $this->mapUserToObject($this->getUserByToken($token), $token);
    }

    /**
     * Determine if the current request / session has a mismatching "state".
     *
     * @return bool
     */
    protected function hasInvalidState(): bool
    {
        if ($this->isStateless()) {
            return false;
        }
        $state = $this->request->session()->pull('state');
        return !(strlen($state) > 0 && $this->request->input('state') === $state);
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
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     * @return array
     */
    protected function getTokenFields(string $code): array
    {
        return [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * Get the code from the request.
     *
     * @return string|null
     */
    protected function getCode()
    {
        return $this->request->input('code');
    }

    /**
     * Merge the scopes of the requested access.
     *
     * @param array|string $scopes
     * @return $this
     */
    public function scopes($scopes)
    {
        $this->scopes = array_unique(array_merge($this->scopes, (array)$scopes));
        return $this;
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param array|string $scopes
     * @return $this
     */
    public function setScopes($scopes)
    {
        $this->scopes = array_unique((array)$scopes);
        return $this;
    }

    /**
     * Get the current scopes.
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set the redirect URL.
     *
     * @param string $url
     * @return $this
     */
    public function redirectUrl(string $url)
    {
        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return ClientInterface
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client($this->guzzle);
        }
        return $this->httpClient;
    }

    /**
     * Set the Guzzle HTTP client instance.
     *
     * @param ClientInterface $client
     * @return $this
     */
    public function setHttpClient(ClientInterface $client)
    {
        $this->httpClient = $client;
        return $this;
    }

    /**
     * Set the request instance.
     *
     * @param \Illuminate\Http\Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function usesState(): bool
    {
        return !$this->stateless;
    }

    /**
     * Determine if the provider is operating as stateless.
     *
     * @return bool
     */
    protected function isStateless(): bool
    {
        return $this->stateless;
    }

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;
        return $this;
    }

    /**
     * Get the string used for session state.
     *
     * @return string
     */
    protected function getState(): string
    {
        return Str::random(40);
    }

    /**
     * Set the custom parameters of the request.
     *
     * @param array $parameters
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }
}
