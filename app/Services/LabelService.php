<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Printing\LabelRendererInterface;
use App\Models\Item;
use App\Models\Location;
use InvalidArgumentException;

final class LabelService {
    public function __construct(private readonly LabelRendererInterface $renderer) {}

    public function generate(string $format, string $entityType, array $entityIds, array $options = []): string {
        $codes = match ($entityType) {
            'locations' => $this->getLocationCodes($entityIds),
            'items'     => $this->getItemCodes($entityIds),
            default     => throw new InvalidArgumentException('Unsupported entity_type'),
        };

        if (empty($codes)) {
            throw new InvalidArgumentException('No codes found for requested entities');
        }

        $format = mb_strtolower($format);
        if ($format === 'zpl') {
            return $this->renderer->renderCodes($codes, $options);
        }

        if ($format === 'png') {
            return $this->generatePngBase64($codes, $options);
        }

        if ($format === 'pdf') {
            return $this->generatePdfBase64($codes, $options);
        }

        throw new InvalidArgumentException('Unsupported format');
    }

    public function generateZpl(string $entityType, array $entityIds, array $options = []): string {
        return $this->generate('zpl', $entityType, $entityIds, $options);
    }

    private function generatePdfBase64(array $codes, array $options): string {
        $generator  = new \Picqer\Barcode\BarcodeGeneratorPNG;
        $height     = (int) ($options['png']['height'] ?? 60);
        $scale      = (int) ($options['png']['scale'] ?? 2);
        $includeHri = (bool) ($options['hri'] ?? true);

        $html = '<html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family:sans-serif;margin:16px;}'
            . '.label{margin:16px auto; text-align:center; page-break-inside:avoid; display:flex; flex-direction:column; align-items:center; justify-content:center;}'
            . '.hri{font-size:12px;margin-top:6px; text-align:center;}'
            . 'img{display:block; margin:0 auto; max-width:100%; height:auto;}'
            . '</style></head><body>';

        foreach ($codes as $code) {
            $png = $generator->getBarcode((string) $code, $generator::TYPE_CODE_128, $scale, $height);
            $b64 = base64_encode($png);
            $html .= '<div class="label">'
                . '<img src="data:image/png;base64,' . $b64 . '" alt="barcode" style="margin-left:auto;margin-right:auto;">';
            if ($includeHri) {
                $html .= '<div class="hri">' . htmlspecialchars((string) $code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</body></html>';

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

        return base64_encode($output);
    }

    private function generatePngBase64(array $codes, array $options): string {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG;
        $height    = (int) ($options['png']['height'] ?? 60);
        $scale     = (int) ($options['png']['scale'] ?? 2);

        $images = [];
        foreach ($codes as $code) {
            $png      = $generator->getBarcode((string) $code, $generator::TYPE_CODE_128, $scale, $height);
            $images[] = base64_encode($png);
        }

        return json_encode($images, JSON_THROW_ON_ERROR);
    }

    private function getItemCodes(array $ids): array {
        $items = Item::query()->whereIn('id', $ids)->get(['id', 'code']);

        return $items->pluck('code')->filter()->values()->all();
    }

    private function getLocationCodes(array $ids): array {
        $locations = Location::query()->whereIn('id', $ids)->get(['id', 'code']);

        return $locations->pluck('code')->filter()->values()->all();
    }
}
