<?php

namespace App\Services;

use App\Enums\DeviceType;

/** Request facts the scan tracker needs, without ever keeping a raw IP. */
class VisitorContext
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|httpclient|headless|lighthouse|monitor/i';

    public static function isBot(?string $userAgent): bool
    {
        return $userAgent === null || $userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    public static function hashIp(?string $ip): string
    {
        return hash('sha256', (string) $ip.config('app.key'));
    }

    public static function deviceType(?string $userAgent): DeviceType
    {
        $ua = (string) $userAgent;

        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua) => DeviceType::Tablet,
            (bool) preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile|kaios/i', $ua) => DeviceType::Mobile,
            (bool) preg_match('/windows|macintosh|x11|linux|cros/i', $ua) => DeviceType::Desktop,
            default => DeviceType::Other,
        };
    }
}
