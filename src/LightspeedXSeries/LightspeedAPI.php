<?php

/**
 * Lightspeed X-Series API Client
 *
 * A PHP client for the Lightspeed X-Series (formerly Vend) API
 * Updated for date-based API versioning (January 2026+)
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedAPI
{
    /**
     * Default API version used when none is specified
     * Format: YYYY-MM (e.g., '2026-01')
     * Update this when migrating your application to a new API version
     */
    private static string $defaultVersion = '2026-01';

    private string $url;
    private LightspeedRequest $request;
    private bool $debug = false;
    private mixed $lastResultRaw = null;
    private mixed $lastResult = null;
    private ?string $instanceVersion = null;

    public bool $allowTimeSlip = false;

    /**
     * @param string $url URL of your store (e.g., https://mystore.retail.lightspeed.app)
     * @param string $tokenType Token type (usually 'Bearer')
     * @param string $accessToken Access token for API
     * @param string|null $version API version for this instance (format: YYYY-MM, e.g., '2026-01')
     * @param string $requestClass Request class for dependency injection/testing
     */
    public function __construct(
        string $url,
        string $tokenType,
        string $accessToken,
        ?string $version = null,
        string $requestClass = LightspeedRequest::class
    ) {
        $this->url = rtrim($url, '/');
        $this->request = new $requestClass($url, $tokenType, $accessToken);

        if ($version !== null) {
            $this->validateVersion($version);
            $this->instanceVersion = $version;
        }
    }

    /**
     * Set the global default API version
     *
     * This affects all new instances that don't specify a version.
     * Call this at the start of your application to set the version site-wide.
     *
     * @param string $version API version in YYYY-MM format (e.g., '2026-01')
     * @throws Exception If version format is invalid
     *
     * @example LightspeedAPI::setDefaultVersion('2026-04');
     */
    public static function setDefaultVersion(string $version): void
    {
        self::validateVersionStatic($version);
        self::$defaultVersion = $version;
    }

    /**
     * Get the current global default API version
     *
     * @return string Current default version (e.g., '2026-01')
     */
    public static function getDefaultVersion(): string
    {
        return self::$defaultVersion;
    }

    /**
     * Set the API version for this specific instance
     *
     * @param string $version API version in YYYY-MM format (e.g., '2026-01')
     * @throws Exception If version format is invalid
     */
    public function setVersion(string $version): void
    {
        $this->validateVersion($version);
        $this->instanceVersion = $version;
    }

    /**
     * Get the API version used by this instance
     *
     * Returns the instance-specific version if set, otherwise the global default.
     *
     * @return string API version (e.g., '2026-01')
     */
    public function getVersion(): string
    {
        return $this->instanceVersion ?? self::$defaultVersion;
    }

    /**
     * Validate API version format
     *
     * @param string $version Version string to validate
     * @throws Exception If version format is invalid
     */
    private function validateVersion(string $version): void
    {
        self::validateVersionStatic($version);
    }

    /**
     * Static version validation for use in static methods
     *
     * @param string $version Version string to validate
     * @throws Exception If version format is invalid
     */
    private static function validateVersionStatic(string $version): void
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $version)) {
            throw new Exception(
                "Invalid API version format: '{$version}'. " .
                "Expected YYYY-MM format (e.g., '2026-01', '2026-04')."
            );
        }
    }

    /**
     * Enable debug mode
     */
    public function debug(bool $status = true): void
    {
        $this->request->setOpt('debug', $status);
        $this->debug = $status;
    }

    /**
     * Get the API path prefix for a given version
     *
     * @param string|null $version API version (YYYY-MM format) or null for default
     * @return string API path prefix
     */
    private function getApiPath(?string $version = null): string
    {
        $version = $version ?? $this->getVersion();
        return '/api/' . $version;
    }

    /**
     * Build query string from options array
     */
    private function buildQueryString(array $options): string
    {
        if (empty($options)) {
            return '';
        }

        return '?' . http_build_query($options);
    }

    /**
     * Make a request to any Lightspeed X-Series API endpoint
     *
     * @param string $endpoint The endpoint path (e.g., 'products', 'customers/123', 'fulfillments')
     * @param string $method HTTP method: 'get', 'post', 'put', 'delete'
     * @param array|null $data Request body data for POST/PUT, or query params for GET
     * @param string|null $version API version override (YYYY-MM format, e.g., '2026-01')
     * @return object API response
     *
     * @example $api->apiRequest('products', 'get');
     * @example $api->apiRequest('products', 'get', ['page_size' => 100]);
     * @example $api->apiRequest('customers', 'post', ['name' => 'John']);
     * @example $api->apiRequest('fulfillments', 'get', null, '2026-04');
     */
    public function apiRequest(string $endpoint, string $method, ?array $data = null, ?string $version = null): object
    {
        if ($version !== null) {
            $this->validateVersion($version);
        }

        $path = $this->getApiPath($version) . '/' . ltrim($endpoint, '/');
        $method = strtolower($method);

        // For GET requests, data becomes query parameters
        if ($method === 'get' && !empty($data)) {
            $path .= $this->buildQueryString($data);
            $data = null;
        }

        $rawResult = match ($method) {
            'get' => $this->request->get($path),
            'post' => $this->request->post($path, json_encode($data)),
            'put' => $this->request->put($path, json_encode($data)),
            'delete' => $this->request->delete($path),
            default => throw new Exception("Invalid HTTP method: {$method}. Use: get, post, put, delete"),
        };

        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        // Handle rate limiting (HTTP 429)
        if ($this->request->httpCode === 429) {
            $retryAfter = isset($result->{'retry-after'}) ? strtotime($result->{'retry-after'}) : time() + 60;

            if ($retryAfter < time()) {
                if ($this->allowTimeSlip) {
                    sleep(60);
                } else {
                    throw new Exception('Rate limit hit and retry-after is in the past. Check system time or set allowTimeSlip = true');
                }
            }

            if ($this->debug) {
                echo "Rate limit hit. Sleeping until " . date('r', $retryAfter) . "\n";
            }

            while (time() < $retryAfter) {
                sleep(1);
            }

            return $this->apiRequest($endpoint, $method, $data, $version);
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error . ' - ' . $rawResult);
        }

        if (isset($result->error)) {
            throw new Exception($result->error . (isset($result->details) ? ': ' . $result->details : ''));
        }

        if ($this->debug) {
            $this->lastResultRaw = $rawResult;
            $this->lastResult = $result;
        }

        return $result;
    }

    /**
     * Make a request to a legacy Lightspeed API endpoint (0.9, 2.0, 2.1, etc.)
     *
     * Use this for endpoints that haven't been migrated to date-based versioning yet,
     * such as register_sales which is still only available on 0.9.
     *
     * @param string $endpoint The endpoint path (e.g., 'register_sales', 'products/123')
     * @param string $method HTTP method: 'get', 'post', 'put', 'delete'
     * @param string $legacyVersion Legacy version string (e.g., '0.9', '2.0', '2.1')
     * @param array|null $data Request body data for POST/PUT, or query params for GET
     * @return object API response
     *
     * @example $api->legacyRequest('register_sales', 'post', '0.9', $saleData);
     * @example $api->legacyRequest('products', 'get', '2.0', ['page_size' => 100]);
     */
    public function legacyRequest(string $endpoint, string $method, string $legacyVersion, ?array $data = null): object
    {
        $path = '/api/' . $legacyVersion . '/' . ltrim($endpoint, '/');
        $method = strtolower($method);

        if ($method === 'get' && !empty($data)) {
            $path .= $this->buildQueryString($data);
            $data = null;
        }

        $rawResult = match ($method) {
            'get' => $this->request->get($path),
            'post' => $this->request->post($path, json_encode($data)),
            'put' => $this->request->put($path, json_encode($data)),
            'delete' => $this->request->delete($path),
            default => throw new Exception("Invalid HTTP method: {$method}. Use: get, post, put, delete"),
        };

        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        if ($this->request->httpCode === 429) {
            $retryAfter = isset($result->{'retry-after'}) ? strtotime($result->{'retry-after'}) : time() + 60;

            if ($retryAfter < time()) {
                if ($this->allowTimeSlip) {
                    sleep(60);
                } else {
                    throw new Exception('Rate limit hit and retry-after is in the past. Check system time or set allowTimeSlip = true');
                }
            }

            if ($this->debug) {
                echo "Rate limit hit. Sleeping until " . date('r', $retryAfter) . "\n";
            }

            while (time() < $retryAfter) {
                sleep(1);
            }

            return $this->legacyRequest($endpoint, $method, $legacyVersion, $data);
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error . ' - ' . $rawResult);
        }

        if (isset($result->error)) {
            throw new Exception($result->error . (isset($result->details) ? ': ' . $result->details : ''));
        }

        if ($this->debug) {
            $this->lastResultRaw = $rawResult;
            $this->lastResult = $result;
        }

        return $result;
    }

    /**
     * Get the HTTP status code from the last request
     */
    public function getLastHttpCode(): int
    {
        return $this->request->httpCode;
    }

    /**
     * Get the raw result from the last request (debug mode only)
     */
    public function getLastResultRaw(): mixed
    {
        return $this->lastResultRaw;
    }

    /**
     * Get the decoded result from the last request (debug mode only)
     */
    public function getLastResult(): mixed
    {
        return $this->lastResult;
    }
}
