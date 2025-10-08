<?php

declare(strict_types = 1);

namespace App\Drivers\Printing;

use App\Contracts\Printing\LabelRendererInterface;

use function count;

final class ZplRenderer implements LabelRendererInterface {
    public function renderCodes(array $codes, array $options = []): string {
        $dpi        = (int) ($options['dpi'] ?? 203);
        $size       = (string) ($options['label_size'] ?? '50x30mm');
        $includeHri = (bool) ($options['hri'] ?? true);

        [$widthMm, $heightMm] = $this->parseSize($size);
        $pw                   = $this->mmToDots($widthMm, $dpi);
        $ll                   = $this->mmToDots($heightMm, $dpi);

        $zpl = '';
        foreach ($codes as $code) {
            $code = (string) $code;
            $zpl .= '^XA';
            $zpl .= '^PW' . $pw;
            $zpl .= '^LL' . $ll;
            $zpl .= '^LH0,0';

            $marginLeft = $this->mmToDots(3.0, $dpi);
            $top        = $this->mmToDots(5.0, $dpi);
            $barHeight  = $this->mmToDots(max(10.0, $heightMm - 15.0), $dpi);

            $zpl .= '^FO' . $marginLeft . ',' . $top;
            $zpl .= '^BY2';
            $zpl .= '^BCN,' . $barHeight . ',N,N,N';
            $zpl .= '^FD' . $this->escape($code) . '^FS';

            if ($includeHri) {
                $fontSize = $this->mmToDots(3.0, $dpi);
                $textTop  = $top + $barHeight + $this->mmToDots(2.0, $dpi);
                $zpl .= '^FO' . $marginLeft . ',' . $textTop . '^A0N,' . $fontSize . ',' . $fontSize . '^FD' . $this->escape($code) . '^FS';
            }

            $zpl .= '^XZ';
        }

        return $zpl;
    }

    private function escape(string $value): string {
        return str_replace(['^', '~'], '', $value);
    }

    private function mmToDots(float $mm, int $dpi): int {
        return (int) round(($mm / 25.4) * $dpi);
    }

    private function parseSize(string $size): array {
        $default = [50.0, 30.0];
        if (! str_contains($size, 'x')) {
            return $default;
        }
        $trimmed = str_replace(['mm', 'MM', ' '], '', $size);
        $parts   = explode('x', $trimmed, 2);
        if (count($parts) !== 2) {
            return $default;
        }
        $w = is_numeric($parts[0]) ? (float) $parts[0] : $default[0];
        $h = is_numeric($parts[1]) ? (float) $parts[1] : $default[1];

        return [$w, $h];
    }
}
