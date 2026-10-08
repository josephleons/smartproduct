{{--
  One view for both the HTML preview and the dompdf PDF, so the preview matches
  the print. Units are mm. The QR is an inline SVG data URI (dompdf-safe).
--}}
@php
  $w = $template['width'];
  $h = $template['height'];
  $qr = max(20, $template['qr_size']);
  $isSheet = $layout === 'sheet';
  $cols = $isSheet ? $template['sheet']['columns'] : 1;
  $rows = $isSheet ? $template['sheet']['rows'] : 1;
  $gap = $isSheet ? $template['sheet']['gap'] : 0;
  $pageW = $isSheet ? 210 : $w;
  $pageH = $isSheet ? 297 : $h;
  $offsetX = $isSheet ? ($pageW - ($cols * $w + ($cols - 1) * $gap)) / 2 : 0;
  $offsetY = $isSheet ? ($pageH - ($rows * $h + ($rows - 1) * $gap)) / 2 : 0;
  $scale = $h / 45;
  $pt = fn ($size) => round($size * $scale, 2) . 'pt';
  $show = fn ($field) => in_array($field, $fields, true);
  $pad = 2.5 * $scale;
  $infoW = $w - $qr - 2.2 * $pad;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <title>{{ __('Labels') }}</title>
  <style>
    @page { size: {{ $pageW }}mm {{ $pageH }}mm; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; color: #1F2D33; }
    .page { position: relative; width: {{ $pageW }}mm; height: {{ $pageH - 0.5 }}mm; overflow: hidden; page-break-after: always; background: #fff; }
    .page:last-child { page-break-after: auto; }
    .label { position: absolute; width: {{ $w }}mm; height: {{ $h }}mm; overflow: hidden;
      @if ($isSheet) border: 0.2mm dashed #c9d3d6; @endif }
    {{-- dompdf positions reliably only with left/top + explicit sizes, so no right/bottom here. --}}
    .info { position: absolute; left: {{ $pad }}mm; top: {{ $pad }}mm; width: {{ $infoW }}mm; height: {{ $h - 2 * $pad }}mm; overflow: hidden; }
    .qr { position: absolute; left: {{ $w - $qr - $pad * 0.6 }}mm; top: {{ max($pad * 0.5, ($h - $qr) / 2 - 2 * $scale) }}mm; width: {{ $qr }}mm; text-align: center; }
    .qr img { width: {{ $qr }}mm; height: {{ $qr }}mm; display: block; }
    .hint { font-size: {{ $pt(5.5) }}; font-weight: bold; color: #0F6680; margin-top: 0.3mm; letter-spacing: 0.2pt; }
    .company { font-size: {{ $pt(6) }}; text-transform: uppercase; color: #0F6680; font-weight: bold; letter-spacing: 0.3pt; }
    .name { font-size: {{ $pt(9) }}; font-weight: bold; line-height: 1.15; margin-top: 0.8mm; }
    .specs { font-size: {{ $pt(6.5) }}; line-height: 1.25; margin-top: 0.8mm; color: #3a4a50; }
    .price { font-size: {{ $pt(11) }}; font-weight: bold; margin-top: 1.2mm; }
    .meta { font-size: {{ $pt(5.5) }}; color: #5c6b70; position: absolute; left: 0; top: {{ $h - 2 * $pad - 3.2 * $scale }}mm; width: {{ $infoW }}mm; }
    @unless ($forPdf)
      {{-- Browser preview only: dompdf treats itself as "screen" media, so keep this out of the PDF. --}}
      @media screen {
        body { background: #e9eef0; padding: 6mm 0; }
        .page { margin: 0 auto 6mm; box-shadow: 0 1px 6px rgba(0, 0, 0, .15); }
      }
      @media print { body { background: #fff; padding: 0; } .page { margin: 0; box-shadow: none; } }
    @endunless
  </style>
</head>
<body>
  @foreach ($pages as $page)
    <div class="page">
      @foreach ($page as $r => $row)
        @foreach ($row as $c => $label)
          <div class="label" style="left: {{ $offsetX + $c * ($w + $gap) }}mm; top: {{ $offsetY + $r * ($h + $gap) }}mm;">
            <div class="info">
              @if ($show('company'))<div class="company">{{ $label['company'] }}</div>@endif
              <div class="name">{{ $label['name'] }}</div>
              @if ($show('specs') && $label['specs'])<div class="specs">{{ $label['specs'] }}</div>@endif
              @if ($show('price'))<div class="price">{{ $label['price'] }}</div>@endif
              <div class="meta">
                @if ($show('branch')){{ $label['branch'] }}@endif
                @if ($show('branch') && $show('sku')) · @endif
                @if ($show('sku')){{ $label['sku'] }}@endif
              </div>
            </div>
            <div class="qr">
              <img src="{{ $label['qr'] }}" alt="QR">
              <div class="hint">{{ __('Scan for specs') }}</div>
            </div>
          </div>
        @endforeach
      @endforeach
    </div>
  @endforeach
  @if (!$forPdf && request()->boolean('print'))
    <script>window.addEventListener('load', () => window.print());</script>
  @endif
</body>
</html>
