<?php

namespace Tests\Unit;

use App\Enums\DeviceType;
use App\Services\PhoneNumber;
use App\Services\VisitorContext;
use PHPUnit\Framework\TestCase;

class VisitorContextTest extends TestCase
{
    public function test_detects_bots_and_devices(): void
    {
        $this->assertTrue(VisitorContext::isBot('Mozilla/5.0 (compatible; Googlebot/2.1)'));
        $this->assertTrue(VisitorContext::isBot('WhatsApp/2.23.20.0'));
        $this->assertTrue(VisitorContext::isBot(null));
        $this->assertFalse(VisitorContext::isBot('Mozilla/5.0 (Linux; Android 12; TECNO) Mobile Safari/537.36'));

        $this->assertSame(DeviceType::Mobile, VisitorContext::deviceType('Mozilla/5.0 (Linux; Android 12; TECNO) Mobile Safari/537.36'));
        $this->assertSame(DeviceType::Tablet, VisitorContext::deviceType('Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X)'));
        $this->assertSame(DeviceType::Desktop, VisitorContext::deviceType('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'));
    }

    public function test_normalises_tanzanian_numbers(): void
    {
        $this->assertSame('255712345678', PhoneNumber::international('0712 345 678'));
        $this->assertSame('255712345678', PhoneNumber::international('+255 712 345 678'));
        $this->assertSame('255712345678', PhoneNumber::international('712345678'));
        $this->assertNull(PhoneNumber::international(''));
    }
}
