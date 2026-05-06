<?php

declare(strict_types=1);

namespace App\Utilities\Handlers;

use Normalizer;
use App\Utilities\Traits\ErrorTrait;

/**
 * NormalizeHandler provides utilities for managing string normalization using PHP's Normalizer class.
 * This handler initializes constants and provides consistent methods for handling string normalization.
 */
class NormalizeHandler
{
    use ErrorTrait;

    /**
     * Normalization-related constants exposed by the handler.
     *
     * @var array<string, int>
     */
    protected readonly array $classConstants;

    /**
     * Constructs the handler and initializes the constants property.
     *
     * @param array $classConstants Placeholder for normalization-related constants.
     */
    public function __construct(
        array $classConstants = []
    ) {
        $this->classConstants = $classConstants !== [] ? $classConstants : [
            'formD'    => Normalizer::FORM_D,     // Canonical decomposition.
            'nfd'      => Normalizer::NFD,       // Alias for FORM_D.
            'formKD'   => Normalizer::FORM_KD,   // Compatibility decomposition.
            'nfkd'     => Normalizer::NFKD,      // Alias for FORM_KD.
            'formC'    => Normalizer::FORM_C,    // Canonical composition.
            'nfc'      => Normalizer::NFC,       // Alias for FORM_C.
            'formKC'   => Normalizer::FORM_KC,   // Compatibility composition.
            'nfkc'     => Normalizer::NFKC,      // Alias for FORM_KC.
            'formKCCF' => Normalizer::FORM_KC_CF, // Compatibility composition for identifiers.
            'nfkcCF'   => Normalizer::NFKC_CF,   // Alias for FORM_KC_CF.
        ];
    }

    // ------------------------------------------------------------------
    //                          Normalization Methods
    // ------------------------------------------------------------------

    /**
     * Normalizes a string using the specified form.
     *
     * @param string $string The input string.
     * @param int $form The normalization form (default: Normalizer::FORM_C).
     * @return string|false The normalized string, or false on failure.
     */
    public function normalize(string $string, int $form = Normalizer::FORM_C): string|false
    {
        return $this->wrapInTry(fn() => Normalizer::normalize($string, $form));
    }

    /**
     * Checks if a string is normalized in the specified form.
     *
     * @param string $string The input string.
     * @param int $form The normalization form (default: Normalizer::FORM_C).
     * @return bool True if the string is normalized, false otherwise.
     */
    public function isNormalized(string $string, int $form = Normalizer::FORM_C): bool
    {
        return $this->wrapInTry(fn() => Normalizer::isNormalized($string, $form));
    }

    /**
     * Retrieves the raw decomposition of a string using the specified form.
     *
     * @param string $string The input string.
     * @param int $form The normalization form (default: Normalizer::FORM_C).
     * @return string|null The raw decomposition string, or null if decomposition fails.
     */
    public function getRawDecomposition(string $string, int $form = Normalizer::FORM_C): ?string
    {
        return $this->wrapInTry(fn() => Normalizer::getRawDecomposition($string, $form));
    }
}
