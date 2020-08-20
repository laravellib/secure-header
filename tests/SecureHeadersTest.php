<?php

namespace codicastudio\SecureHeaders\Tests;

use codicastudio\SecureHeaders\SecureHeaders;
use InvalidArgumentException;

final class SecureHeadersTest extends TestCase
{
    /**
     * @var string
     */
    protected $configPath = __DIR__ . '/../config/secure-header.php';

    public function testSendHeaders()
    {
        (new SecureHeaders($this->config()))->send();

        $headers = xdebug_get_headers();

        $this->assertContains(
            'X-Content-Type-Options: nosniff',
            $headers
        );

        $this->assertContains(
            'Referrer-Policy: no-referrer',
            $headers
        );
    }

    public function testDisableHeader()
    {
        $config = $this->config();

        $config['x-download-options'] = null;

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayHasKey('X-Frame-Options', $headers);

        $this->assertArrayNotHasKey('X-Download-Options', $headers);
    }

    public function testLoadFromFile()
    {
        $headers = SecureHeaders::fromFile($this->configPath)->headers();

        $this->assertArrayHasKey('Feature-Policy', $headers);

        $this->assertArrayHasKey('X-XSS-Protection', $headers);
    }

    public function testFileNotFound()
    {
        $this->expectException(InvalidArgumentException::class);

        SecureHeaders::fromFile(__DIR__ . '/not-found');
    }

    public function testServerHeader()
    {
        $config = $this->config();

        $this->assertArrayNotHasKey(
            'Server',
            (new SecureHeaders($config))->headers()
        );

        $config['server'] = 'Example';

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayHasKey('Server', $headers);

        $this->assertSame('Example', $headers['Server']);
    }

    public function testXPoweredByHeader()
    {
        $config = $this->config();

        $this->assertArrayNotHasKey(
            'X-Powered-By',
            (new SecureHeaders($config))->headers()
        );

        $config['x-powered-by'] = 'Example';

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayHasKey('X-Powered-By', $headers);

        $this->assertSame('Example', $headers['X-Powered-By']);

        // ensure backward compatibility

        unset($config['x-powered-by']);

        $this->assertArrayNotHasKey(
            'X-Powered-By',
            (new SecureHeaders($config))->headers()
        );

        $config['x-power-by'] = 'Example';

        $this->assertArrayHasKey('X-Powered-By', $headers);

        $this->assertSame('Example', $headers['X-Powered-By']);
    }

    public function testContentSecurityPolicy()
    {
        $config = $this->config();

        $config['csp']['enable'] = true;

        $this->assertArrayNotHasKey(
            'Content-Security-Policy',
            (new SecureHeaders($config))->headers()
        );

        $config['csp']['default-src']['self'] = true;

        $this->assertArrayHasKey(
            'Content-Security-Policy',
            (new SecureHeaders($config))->headers()
        );

        $config['csp']['report-only'] = true;

        $this->assertArrayHasKey(
            'Content-Security-Policy-Report-Only',
            (new SecureHeaders($config))->headers()
        );

        $this->assertArrayNotHasKey(
            'Content-Security-Policy',
            (new SecureHeaders($config))->headers()
        );

        $config['csp']['enable'] = false;

        $this->assertArrayNotHasKey(
            'Content-Security-Policy',
            (new SecureHeaders($config))->headers()
        );
    }

    public function testContentSecurityPolicyNonce()
    {
        $nonce = SecureHeaders::nonce();

        $headers = (new SecureHeaders($this->config()))->headers();

        $this->assertArrayHasKey(
            'Content-Security-Policy',
            $headers
        );

        $this->assertSame(
            sprintf("script-src 'nonce-%s'", $nonce),
            $headers['Content-Security-Policy']
        );
    }

    public function testContentSecurityPolicyNonceWillBeClearedAfterHeaderSent()
    {
        $times = 10;

        while ($times--) {
            $nonce = SecureHeaders::nonce();

            $headers = (new SecureHeaders($this->config()))->headers();

            $this->assertSame(
                sprintf("script-src 'nonce-%s'", $nonce),
                $headers['Content-Security-Policy']
            );
        }
    }

    public function testFeaturePolicy()
    {
        $config = $this->config();

        $config['feature-policy']['enable'] = true;

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayHasKey('Feature-Policy', $headers);

        $this->assertArrayNotHasKey('Permissions-Policy', $headers);

        $config['feature-policy']['use-permissions-policy-header'] = true;

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayHasKey('Permissions-Policy', $headers);

        $this->assertArrayNotHasKey('Feature-Policy', $headers);

        $config['feature-policy']['enable'] = false;

        $headers = (new SecureHeaders($config))->headers();

        $this->assertArrayNotHasKey('Feature-Policy', $headers);

        $this->assertArrayNotHasKey('Permissions-Policy', $headers);
    }

    public function testStrictTransportSecurity()
    {
        $config = $this->config();

        $config['hsts']['enable'] = true;

        $this->assertArrayHasKey(
            'Strict-Transport-Security',
            (new SecureHeaders($config))->headers()
        );

        $config['hsts']['enable'] = false;

        $this->assertArrayNotHasKey(
            'Strict-Transport-Security',
            (new SecureHeaders($config))->headers()
        );
    }

    public function testExpectCT()
    {
        $config = $this->config();

        $config['expect-ct']['enable'] = true;

        $this->assertArrayHasKey(
            'Expect-CT',
            (new SecureHeaders($config))->headers()
        );

        $config['expect-ct']['enable'] = false;

        $this->assertArrayNotHasKey(
            'Expect-CT',
            (new SecureHeaders($config))->headers()
        );
    }

    public function testClearSiteData()
    {
        $config = $this->config();

        $config['clear-site-data']['enable'] = true;

        $this->assertArrayHasKey(
            'Clear-Site-Data',
            (new SecureHeaders($config))->headers()
        );

        $config['clear-site-data']['enable'] = false;

        $this->assertArrayNotHasKey(
            'Clear-Site-Data',
            (new SecureHeaders($config))->headers()
        );
    }

    /**
     * Get secure-header config.
     *
     * @return array<mixed>
     */
    protected function config(): array
    {
        return require $this->configPath;
    }
}
