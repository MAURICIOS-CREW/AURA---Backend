<?php

namespace App\Contracts;

interface LicensePlateNormalizerInterface
{
    /**
     * Normalizes a raw license plate string to an uppercase alphanumeric format.
     * Removes spaces, hyphens, diacritics, and non-alphanumeric characters.
     *
     * @param string $plate
     * @return string
     */
    public function normalize(string $plate): string;
}
