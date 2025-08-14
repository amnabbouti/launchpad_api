<?php

declare(strict_types = 1);

namespace App\Contracts\Printing;

interface LabelRendererInterface {
    /**
     * Render a list of codes into the target format payload (e.g., ZPL, PNG).
     */
    public function renderCodes(array $codes, array $options = []): string;
}
