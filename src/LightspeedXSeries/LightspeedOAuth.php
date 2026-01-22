<?php

/**
 * Lightspeed X-Series OAuth2 Client
 *
 * Handles OAuth2 authentication for the Lightspeed X-Series API
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedOAuth {
    private string $clientId;
    private string $clientSecret;
    private string $domainPrefix;
    private string $redirectUri;
    private array $scopes;

    /**
     * @param string $clientId OAuth client ID
     * @param string $clientSecret OAuth client secret
     * @param string $domainPrefix Your Lightspeed store prefix (e.g., 'mystore')
     * @param string $redirectUri OAuth redirect URI
     * @param array $scopes OAuth scopes (required from March 2026)
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $domainPrefix,
        string $redirectUri,
        array $scopes = []
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->domainPrefix = $domainPrefix;
        $this->redirectUri = $redirectUri;
        $this->scopes = $scopes;
    }

    /**
     * Get the base URL for OAuth endpoints
     */
    private function getBaseUrl(): string {
        return 'https://' . $this->domainPrefix . '.retail.lightspeed.app';
    }

    /**
     * Generate a random state string for CSRF protection
     */
    public function generateState(): string {
        return bin2hex(random_bytes(16));
    }

    /**
     * Build the authorization URL for OAuth flow
     *
     * @param string|null $state CSRF state (will generate if not provided)
     * @return array ['url' => string, 'state' => string]
     */
    public function getAuthorizationUrl(?string $state = null): array {
        $state = $state ?? $this->generateState();

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ];

        // Add scopes if provided (required from March 2026)
        if (!empty($this->scopes)) {
            $params['scope'] = implode(' ', $this->scopes);
        }

        $url = $this->getBaseUrl() . '/connect?' . http_build_query($params);

        return [
            'url' => $url,
            'state' => $state,
        ];
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code Authorization code from OAuth callback
     * @return array Token response ['access_token', 'refresh_token', 'expires_in', etc.]
     * @throws Exception
     */
    public function exchangeCodeForToken(string $code): array {
        $url = $this->getBaseUrl() . '/api/1.0/token';

        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        return $this->makeTokenRequest($url, $data);
    }

    /**
     * Refresh an expired access token
     *
     * @param string $refreshToken The refresh token
     * @return array New token response ['access_token', 'refresh_token', 'expires_in', etc.]
     * @throws Exception
     */
    public function refreshToken(string $refreshToken): array {
        $url = $this->getBaseUrl() . '/api/1.0/token';

        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ];

        return $this->makeTokenRequest($url, $data);
    }

    /**
     * Make a token request to the OAuth endpoint
     *
     * @param string $url Token endpoint URL
     * @param array $data POST data
     * @return array Token response
     * @throws Exception
     */
    private function makeTokenRequest(string $url, array $data): array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception('OAuth request failed: ' . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $result['error_description'] ?? $result['error'] ?? 'Unknown error';
            throw new Exception('OAuth error: ' . $errorMessage, $httpCode);
        }

        if ($result === null) {
            throw new Exception('Invalid JSON response from OAuth endpoint');
        }

        return $result;
    }

    /**
     * Set OAuth scopes
     *
     * @param array $scopes Array of scope strings
     */
    public function setScopes(array $scopes): self {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Get currently configured scopes
     */
    public function getScopes(): array {
        return $this->scopes;
    }
}
