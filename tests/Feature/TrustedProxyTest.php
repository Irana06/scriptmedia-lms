<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_proto_is_ignored_without_trusted_proxies(): void
    {
        $this->get('/', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertSee('rel="manifest" href="http://', false);
    }

    public function test_forwarded_proto_from_a_trusted_proxy_makes_urls_https(): void
    {
        TrustProxies::at(['127.0.0.1']);

        try {
            $this->get('/', ['X-Forwarded-Proto' => 'https'])
                ->assertOk()
                ->assertSee('rel="manifest" href="https://', false);
        } finally {
            TrustProxies::flushState();
        }
    }
}
