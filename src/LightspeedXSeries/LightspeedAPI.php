<?php

/**
 * Lightspeed X-Series API Client
 *
 * A PHP client for the Lightspeed X-Series (formerly Vend) API
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
    // API version constants
    public const VERSION_09 = '0.9';
    public const VERSION_20 = '2.0';
    public const VERSION_20_BETA = '2.0b';
    public const VERSION_21 = '2.1';
    public const VERSION_30 = '3.0';
    public const VERSION_30_BETA = '3.0b';

    private string $url;
    private LightspeedRequest $request;
    private bool $debug = false;
    private mixed $lastResultRaw = null;
    private mixed $lastResult = null;

    public bool $allowTimeSlip = false;

    /**
     * @param string $url URL of your store (e.g., https://mystore.retail.lightspeed.app)
     * @param string $tokenType Token type (usually 'Bearer')
     * @param string $accessToken Access token for API
     * @param string $requestClass Request class for dependency injection/testing
     */
    public function __construct(
        string $url,
        string $tokenType,
        string $accessToken,
        string $requestClass = LightspeedRequest::class
    ) {
        $this->url = rtrim($url, '/');
        $this->request = new $requestClass($url, $tokenType, $accessToken);
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
     * @param string $version API version (use VERSION_* constants)
     * @return string API path prefix
     */
    private function getApiPath(string $version): string
    {
        return match ($version) {
            self::VERSION_09 => '/api',
            self::VERSION_20 => '/api/2.0',
            self::VERSION_20_BETA => '/api/2.0',
            self::VERSION_21 => '/api/2.1',
            self::VERSION_30 => '/api/3.0',
            self::VERSION_30_BETA => '/api/3.0',
            default => '/api/2.0',
        };
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
     * @param string $version API version (default: '2.0'). Options: '0.9', '2.0', '2.0b', '2.1', '3.0', '3.0b'
     * @param array|null $data Request body data for POST/PUT, or query params for GET
     * @return object API response
     *
     * @example $api->apiRequest('products', 'get');
     * @example $api->apiRequest('products', 'get', '2.0', ['page_size' => 100]);
     * @example $api->apiRequest('customers', 'post', '2.0', ['name' => 'John']);
     * @example $api->apiRequest('fulfillments', 'get', '3.0b');
     */
    public function apiRequest(string $endpoint, string $method, string $version = self::VERSION_20, ?array $data = null): object
    {
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

            return $this->apiRequest($endpoint, $method, $version, $data);
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
