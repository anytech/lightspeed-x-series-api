<?php

namespace LightspeedXSeries\Tests;

use LightspeedXSeries\LightspeedRequest;

class MockLightspeedRequest extends LightspeedRequest
{
    public string $lastPath = '';
    public string $lastMethod = '';
    public ?string $lastData = null;
    public string $mockResponse = '{}';

    public function __construct(string $url, string $tokenType, string $accessToken)
    {
        // Don't call parent - we don't want actual curl initialization
    }

    public function __destruct()
    {
        // Override to prevent parent from accessing uninitialized $curl
    }

    public function setOpt(string|int|array $name, mixed $value = false): void
    {
        // Mock setOpt - do nothing
    }

    public function get(string $path): string
    {
        $this->lastPath = $path;
        $this->lastMethod = 'get';
        return $this->mockResponse;
    }

    public function post(string $path, string $rawdata): string
    {
        $this->lastPath = $path;
        $this->lastMethod = 'post';
        $this->lastData = $rawdata;
        return $this->mockResponse;
    }

    public function put(string $path, string $rawdata): string
    {
        $this->lastPath = $path;
        $this->lastMethod = 'put';
        $this->lastData = $rawdata;
        return $this->mockResponse;
    }

    public function delete(string $path): string
    {
        $this->lastPath = $path;
        $this->lastMethod = 'delete';
        return $this->mockResponse;
    }
}
