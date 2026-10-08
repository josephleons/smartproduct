<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeRenderer
{
    /** Quiet zone, in modules, around the code. */
    public const MARGIN = 2;

    /** SVG with error correction level M and a quiet zone. */
    public function svg(string $url, int $sizePx = 300): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($sizePx, self::MARGIN),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($url, Encoder::DEFAULT_BYTE_MODE_ENCODING, ErrorCorrectionLevel::M());
    }

    /** Data URI so the same <img> works in the HTML preview and in dompdf. */
    public function dataUri(string $url): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($url));
    }
}
