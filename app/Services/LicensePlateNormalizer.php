<?php

namespace App\Services;

use App\Contracts\LicensePlateNormalizerInterface;

class LicensePlateNormalizer implements LicensePlateNormalizerInterface
{
    /**
     * Normalizes a raw license plate string to an uppercase alphanumeric format.
     * Removes hyphens, spaces, accents, and any special characters.
     *
     * Example: " abc - 123!" -> "ABC123"
     *
     * @param string $plate
     * @return string
     */
    public function normalize(string $plate): string
    {
        // 1. Remove surrounding whitespace
        $cleaned = trim($plate);

        // 2. Remove diacritics / accents (e.g. Á -> A, É -> E)
        if (function_exists('transliterator_transliterate')) {
            $cleaned = transliterator_transliterate('Any-Latin; Latin-ASCII', $cleaned) ?: $cleaned;
        } else {
            $cleaned = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cleaned) ?: $cleaned;
        }

        // 3. Convert to uppercase
        $cleaned = mb_strtoupper($cleaned, 'UTF-8');

        // 4. Remove all non-alphanumeric characters (A-Z, 0-9)
        $normalized = preg_replace('/[^A-Z0-9]/', '', $cleaned);

        return $normalized ?? '';
    }
}
