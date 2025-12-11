<?php

/**
 * Lightspeed X-Series HTTP Request Handler
 *
 * Handles HTTP requests to the Lightspeed X-Series API
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedRequest
{
    private \CurlHandle|false $curl;
    private bool $debug = false;
    private string $url;
    private string $httpHeader = '';
    private string $httpBody = '';
    private string $posted = '';

    public int $httpCode = 0;

    /**
     * @param string $url Base URL for the API
     * @param string $tokenType Token type (usually 'Bearer')
     * @param string $accessToken Access token for authentication
     */
    public function __construct(string $url, string $tokenType, string $accessToken)
    {
        $this->curl = curl_init();
        $this->url = rtrim($url, '/');

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: ' . $tokenType . ' ' . $accessToken,
            ],
            CURLOPT_HEADER => true,
        ];

        $this->setOpt($options);
    }

    public function __destruct()
    {
        if ($this->curl instanceof \CurlHandle) {
            curl_close($this->curl);
        }
    }

    /**
     * Set cURL option(s)
     *
     * @param string|int|array $name Option name or array of options
     * @param mixed $value Option value (if $name is not an array)
     */
    public function setOpt(string|int|array $name, mixed $value = false): void
    {
        if (is_array($name)) {
            curl_setopt_array($this->curl, $name);
            return;
        }

        if ($name === 'debug') {
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, (int)$value);
            curl_setopt($this->curl, CURLOPT_VERBOSE, (bool)$value);
            $this->debug = (bool)$value;
        } else {
            curl_setopt($this->curl, $name, $value);
        }
    }

    /**
     * Make a POST request
     *
     * @param string $path API path
     * @param string $rawdata JSON data to send
     * @return string Response body
     */
    public function post(string $path, string $rawdata): string
    {
        $this->setOpt([
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $rawdata,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ]);
        $this->posted = $rawdata;

        return $this->request($path);
    }

    /**
     * Make a PUT request
     *
     * @param string $path API path
     * @param string $rawdata JSON data to send
     * @return string Response body
     */
    public function put(string $path, string $rawdata): string
    {
        $this->setOpt([
            CURLOPT_POSTFIELDS => $rawdata,
            CURLOPT_CUSTOMREQUEST => 'PUT',
        ]);
        $this->posted = $rawdata;

        return $this->request($path);
    }

    /**
     * Make a DELETE request
     *
     * @param string $path API path
     * @return string Response body
     */
    public function delete(string $path): string
    {
        $this->setOpt([
            CURLOPT_HTTPGET => false,
            CURLOPT_POSTFIELDS => null,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ]);
        $this->posted = '';

        return $this->request($path);
    }

    /**
     * Make a GET request
     *
     * @param string $path API path
     * @return string Response body
     */
    public function get(string $path): string
    {
        $this->setOpt([
            CURLOPT_HTTPGET => true,
            CURLOPT_POSTFIELDS => null,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
        $this->posted = '';

        return $this->request($path);
    }

    /**
     * Execute the HTTP request
     *
     * @param string $path API path
     * @return string Response body
     */
    private function request(string $path): string
    {
        $this->setOpt(CURLOPT_URL, $this->url . $path);

        $response = curl_exec($this->curl);
        $curlStatus = curl_getinfo($this->curl);

        $this->httpCode = $curlStatus['http_code'];
        $headerSize = $curlStatus['header_size'];

        $this->httpHeader = substr($response, 0, $headerSize);
        $this->httpBody = substr($response, $headerSize);

        if ($this->debug) {
            $head = $foot = "\n";
            if (php_sapi_name() !== 'cli') {
                $head = '<pre>';
                $foot = '</pre>';
            }

            echo $head . ($curlStatus['request_header'] ?? '') . $foot;
            if ($this->posted) {
                echo $head . $this->posted . $foot;
            }
            echo $head . $this->httpHeader . $foot;
            echo $head . htmlentities($this->httpBody) . $foot;
        }

        return $this->httpBody;
    }
}
