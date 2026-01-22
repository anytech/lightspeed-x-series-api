<?php

namespace LightspeedXSeries\Tests;

use PHPUnit\Framework\TestCase;
use LightspeedXSeries\LightspeedAPI;
use LightspeedXSeries\Exception;

class LightspeedAPITest extends TestCase
{
    private string $originalDefaultVersion;

    protected function setUp(): void
    {
        $this->originalDefaultVersion = LightspeedAPI::getDefaultVersion();
    }

    protected function tearDown(): void
    {
        LightspeedAPI::setDefaultVersion($this->originalDefaultVersion);
    }

    private function createApi(?string $version = null): LightspeedAPI
    {
        return new LightspeedAPI(
            'https://mystore.retail.lightspeed.app',
            'Bearer',
            'test-token',
            $version,
            MockLightspeedRequest::class
        );
    }

    public function testValidVersionFormats(): void
    {
        $validVersions = [
            '2026-01',
            '2026-04',
            '2026-12',
            '2025-06',
            '2030-09',
        ];

        foreach ($validVersions as $version) {
            $api = $this->createApi($version);
            $this->assertEquals($version, $api->getVersion());
        }
    }

    public function testInvalidVersionFormatThrowsException(): void
    {
        $invalidVersions = [
            '2026-1',      // Single digit month
            '2026-13',     // Invalid month
            '2026-00',     // Invalid month
            '26-01',       // Short year
            '2026/01',     // Wrong separator
            '2026.01',     // Wrong separator
            '202601',      // No separator
            'latest',      // Non-numeric
            '',            // Empty
        ];

        foreach ($invalidVersions as $version) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Invalid API version format");
            $this->createApi($version);
        }
    }

    public function testInvalidVersionFormatWithDash(): void
    {
        $this->expectException(Exception::class);
        $this->createApi('2026-1');
    }

    public function testInvalidVersionFormatMonth13(): void
    {
        $this->expectException(Exception::class);
        $this->createApi('2026-13');
    }

    public function testInvalidVersionFormatMonth00(): void
    {
        $this->expectException(Exception::class);
        $this->createApi('2026-00');
    }

    public function testDefaultVersionIsUsedWhenNoneSpecified(): void
    {
        $api = $this->createApi();
        $this->assertEquals(LightspeedAPI::getDefaultVersion(), $api->getVersion());
    }

    public function testSetDefaultVersionAffectsNewInstances(): void
    {
        LightspeedAPI::setDefaultVersion('2027-06');

        $api = $this->createApi();
        $this->assertEquals('2027-06', $api->getVersion());
    }

    public function testInstanceVersionOverridesDefault(): void
    {
        LightspeedAPI::setDefaultVersion('2026-01');

        $api = $this->createApi('2027-03');
        $this->assertEquals('2027-03', $api->getVersion());
    }

    public function testSetVersionChangesInstanceVersion(): void
    {
        $api = $this->createApi('2026-01');
        $this->assertEquals('2026-01', $api->getVersion());

        $api->setVersion('2026-06');
        $this->assertEquals('2026-06', $api->getVersion());
    }

    public function testSetVersionWithInvalidFormatThrowsException(): void
    {
        $api = $this->createApi('2026-01');

        $this->expectException(Exception::class);
        $api->setVersion('invalid');
    }

    public function testSetDefaultVersionWithInvalidFormatThrowsException(): void
    {
        $this->expectException(Exception::class);
        LightspeedAPI::setDefaultVersion('invalid');
    }

    public function testApiRequestBuildsCorrectPath(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"data": []}';
        $api->apiRequest('products', 'get');

        $this->assertEquals('/api/2026-01/products', $mockRequest->lastPath);
    }

    public function testApiRequestWithVersionOverride(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"data": []}';
        $api->apiRequest('products', 'get', null, '2026-04');

        $this->assertEquals('/api/2026-04/products', $mockRequest->lastPath);
    }

    public function testApiRequestWithQueryParameters(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"data": []}';
        $api->apiRequest('products', 'get', ['page_size' => 100, 'after' => 'cursor123']);

        $this->assertStringContainsString('/api/2026-01/products?', $mockRequest->lastPath);
        $this->assertStringContainsString('page_size=100', $mockRequest->lastPath);
        $this->assertStringContainsString('after=cursor123', $mockRequest->lastPath);
    }

    public function testApiRequestPostMethod(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"id": "123"}';
        $api->apiRequest('products', 'post', ['name' => 'Test Product']);

        $this->assertEquals('post', $mockRequest->lastMethod);
        $this->assertEquals('/api/2026-01/products', $mockRequest->lastPath);
        $this->assertStringContainsString('Test Product', $mockRequest->lastData);
    }

    public function testApiRequestInvalidMethodThrowsException(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);
        $mockRequest->mockResponse = '{}';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid HTTP method');
        $api->apiRequest('products', 'patch');
    }

    public function testLegacyRequestWithVersion09(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"register_sales": []}';
        $api->legacyRequest('register_sales', 'get', '0.9');

        $this->assertEquals('/api/register_sales', $mockRequest->lastPath);
    }

    public function testLegacyRequestWithVersion20(): void
    {
        $api = $this->createApi('2026-01');

        $reflection = new \ReflectionClass($api);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $mockRequest = $requestProperty->getValue($api);

        $mockRequest->mockResponse = '{"products": []}';
        $api->legacyRequest('products', 'get', '2.0');

        $this->assertEquals('/api/2.0/products', $mockRequest->lastPath);
    }

    public function testUrlIsNormalized(): void
    {
        $api1 = new LightspeedAPI(
            'https://mystore.retail.lightspeed.app/',
            'Bearer',
            'test-token',
            '2026-01',
            MockLightspeedRequest::class
        );

        $api2 = new LightspeedAPI(
            'https://mystore.retail.lightspeed.app',
            'Bearer',
            'test-token',
            '2026-01',
            MockLightspeedRequest::class
        );

        $reflection = new \ReflectionClass($api1);
        $urlProperty = $reflection->getProperty('url');
        $urlProperty->setAccessible(true);

        $this->assertEquals(
            $urlProperty->getValue($api1),
            $urlProperty->getValue($api2)
        );
    }
}
