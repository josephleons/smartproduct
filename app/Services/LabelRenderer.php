<?php

namespace App\Services;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Builds label sheets from config/labels.php. The same Blade view renders the
 * HTML preview and the PDF, so what the seller previews is what prints.
 */
class LabelRenderer
{
    public function __construct(private QrCodeRenderer $qr) {}

    /** @return array<string, mixed> */
    public function template(string $key): array
    {
        $template = config("labels.templates.$key");

        if (! $template) {
            throw new InvalidArgumentException("Unknown label template [$key].");
        }

        return $template + ['key' => $key];
    }

    /**
     * @param  Collection<int, Product>  $products  with company, branch, specs and qrCode loaded
     * @param  array<int, string>  $fields
     * @return array<string, mixed> view data for labels.sheet
     */
    public function viewData(Collection $products, string $templateKey, string $layout, array $fields, int $copies): array
    {
        $template = $this->template($templateKey);

        $labels = $products->flatMap(fn (Product $product) => array_fill(0, $copies, $product))
            ->map(fn (Product $product) => [
                'company' => $product->company->name,
                'name' => $product->name(),
                'specs' => $product->labelSpecLine(),
                'price' => money($product->price),
                'branch' => $product->branch->name,
                'sku' => $product->sku,
                'qr' => $this->qr->dataUri($product->qrCode->url()),
            ]);

        if ($layout === 'sheet') {
            $perPage = $template['sheet']['columns'] * $template['sheet']['rows'];
            // chunk() keeps keys; reset them so row/column indexes start at 0 on every page.
            $pages = $labels->values()->chunk($perPage)->map(
                fn ($page) => $page->values()->chunk($template['sheet']['columns'])->map->values()->values()
            );
        } else {
            $pages = $labels->map(fn ($label) => collect([collect([$label])]));
        }

        return [
            'template' => $template,
            'layout' => $layout,
            'fields' => $fields,
            'pages' => $pages->values(),
        ];
    }

    public function pdf(array $viewData): \Barryvdh\DomPDF\PDF
    {
        $template = $viewData['template'];

        $paper = $viewData['layout'] === 'sheet'
            ? $template['sheet']['paper']
            : [0, 0, $this->mmToPt($template['width']), $this->mmToPt($template['height'])];

        return Pdf::loadView('labels.sheet', $viewData + ['forPdf' => true])
            ->setPaper($paper, 'portrait')
            ->setOption(['dpi' => 300, 'isRemoteEnabled' => false]);
    }

    private function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
