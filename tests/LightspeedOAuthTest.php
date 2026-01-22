<?php

namespace LightspeedXSeries\Tests;

use PHPUnit\Framework\TestCase;
use LightspeedXSeries\LightspeedOAuth;

class LightspeedOAuthTest extends TestCase
{
    private function createOAuth(array $scopes = []): LightspeedOAuth
    {
        return new LightspeedOAuth(
            'test-client-id',
            'test-client-secret',
            'mystore',
            'https://example.com/callback',
            $scopes
        );
    }

    public function testGetAuthorizationUrlContainsRequiredParameters(): void
    {
        $oauth = $this->createOAuth();
        $result = $oauth->getAuthorizationUrl();

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('state', $result);

        $url = $result['url'];
        $this->assertStringContainsString('https://mystore.retail.lightspeed.app/connect?', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://example.com/callback'), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function testGetAuthorizationUrlWithCustomState(): void
    {
        $oauth = $this->createOAuth();
        $customState = 'my-custom-state-123';
        $result = $oauth->getAuthorizationUrl($customState);

        $this->assertEquals($customState, $result['state']);
        $this->assertStringContainsString('state=' . $customState, $result['url']);
    }

    public function testGetAuthorizationUrlWithScopes(): void
    {
        $scopes = ['products:read', 'customers:read', 'sales:write'];
        $oauth = $this->createOAuth($scopes);
        $result = $oauth->getAuthorizationUrl();

        $this->assertStringContainsString('scope=', $result['url']);
        $this->assertStringContainsString(urlencode('products:read customers:read sales:write'), $result['url']);
    }

    public function testGetAuthorizationUrlWithoutScopes(): void
    {
        $oauth = $this->createOAuth([]);
        $result = $oauth->getAuthorizationUrl();

        $this->assertStringNotContainsString('scope=', $result['url']);
    }

    public function testGenerateStateReturnsUniqueValues(): void
    {
        $oauth = $this->createOAuth();

        $states = [];
        for ($i = 0; $i < 10; $i++) {
            $states[] = $oauth->generateState();
        }

        $uniqueStates = array_unique($states);
        $this->assertCount(10, $uniqueStates, 'Generated states should be unique');
    }

    public function testGenerateStateIsCorrectLength(): void
    {
        $oauth = $this->createOAuth();
        $state = $oauth->generateState();

        // bin2hex(random_bytes(16)) produces 32 characters
        $this->assertEquals(32, strlen($state));
    }

    public function testGenerateStateIsHexadecimal(): void
    {
        $oauth = $this->createOAuth();
        $state = $oauth->generateState();

        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $state);
    }

    public function testSetScopesUpdatesScopes(): void
    {
        $oauth = $this->createOAuth();
        $this->assertEmpty($oauth->getScopes());

        $newScopes = ['products:read', 'sales:write'];
        $oauth->setScopes($newScopes);

        $this->assertEquals($newScopes, $oauth->getScopes());
    }

    public function testSetScopesReturnsSelf(): void
    {
        $oauth = $this->createOAuth();
        $result = $oauth->setScopes(['products:read']);

        $this->assertSame($oauth, $result);
    }

    public function testSetScopesAffectsAuthorizationUrl(): void
    {
        $oauth = $this->createOAuth();

        // First, get URL without scopes
        $result1 = $oauth->getAuthorizationUrl();
        $this->assertStringNotContainsString('scope=', $result1['url']);

        // Set scopes and get URL again
        $oauth->setScopes(['products:read']);
        $result2 = $oauth->getAuthorizationUrl();
        $this->assertStringContainsString('scope=', $result2['url']);
        $this->assertStringContainsString('products%3Aread', $result2['url']);
    }

    public function testGetScopesReturnsConfiguredScopes(): void
    {
        $scopes = ['products:read', 'customers:write'];
        $oauth = $this->createOAuth($scopes);

        $this->assertEquals($scopes, $oauth->getScopes());
    }

    public function testAuthorizationUrlUsesCorrectDomainPrefix(): void
    {
        $oauth = new LightspeedOAuth(
            'client-id',
            'client-secret',
            'different-store',
            'https://example.com/callback'
        );

        $result = $oauth->getAuthorizationUrl();

        $this->assertStringContainsString('https://different-store.retail.lightspeed.app', $result['url']);
    }
}
