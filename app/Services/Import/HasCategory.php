<?php

namespace App\Services\Import;

interface HasCategory
{
    /**
     * Extract category from a CSV row.
     * Return null if category is not available in this import type.
     */
    public function extractCategory(array $row): ?string;
}
