<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\CryptoException;
use App\Utilities\Traits\{
    ArrayTrait,
    CheckerTrait,
    ExistenceCheckerTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Abstract class defining the core cryptographic operations.
 * 
 * This class provides a blueprint for cryptographic drivers
 * such as SodiumCrypto and OpenSSLCrypto. It enforces
 * the implementation of essential cryptographic functionalities.
 */
abstract class Crypto
{
    use ArrayTrait, CheckerTrait, ExistenceCheckerTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait;

    /**
     * Returns the normalized runtime driver name.
     */
    abstract public function driverName(): string;

    /**
     * Returns the runtime capability map for the driver.
     *
     * @return array<string, mixed>
     */
    abstract public function capabilities(): array;

    /**
     * Checks whether the driver supports a capability or feature path.
     */
    public function supports(string $feature): bool
    {
        $resolved = $this->resolveCapability($feature);

        return match (true) {
            $this->isBool($resolved) => $resolved,
            $this->isArray($resolved) => !$this->isEmpty($resolved),
            $this->isString($resolved) => $resolved !== '',
            default => $resolved !== null,
        };
    }

    /**
     * Handles data conversions such as base64 encoding, hex conversions, etc.
     * 
     * @param string $type The type of data conversion to perform.
     *                     Supported types: bin2base64, base642bin, bin2hex, hex2bin.
     * @return callable Returns a callable function for the specified conversion.
     * 
     * @throws CryptoException If the type of data conversion is unsupported.
     */
    abstract public function DataConverter(string $type): callable;

    /**
     * Handles encryption operations.
     * 
     * @param string $type The type of encryption to perform.
     *                     Example: symmetric, asymmetric, AEAD.
     * @return callable Returns a callable function for encryption.
     * 
     * @throws CryptoException If the encryption type is unsupported.
     */
    abstract public function Encryptor(string $type): callable;

    /**
     * Handles decryption operations.
     * 
     * @param string $type The type of decryption to perform.
     *                     Example: symmetric, asymmetric, AEAD.
     * @return callable Returns a callable function for decryption.
     * 
     * @throws CryptoException If the decryption type is unsupported.
     */
    abstract public function Decryptor(string $type): callable;

    /**
     * Generates secure random bytes for various cryptographic purposes.
     * 
     * @param string $type The type of random data to generate.
     *                     Example: default, passwordSalt, scalar.
     * @param int|null $length Optional length of the random data in bytes.
     * @return callable Returns a callable function for random byte generation.
     * 
     * @throws CryptoException If the random generator type is unsupported.
     */
    abstract public function RandomGenerator(string $type, ?int $length = null): callable;

    /**
     * Provides hashing functionality.
     * 
     * @param string $type The type of hash operation to perform.
     *                     Supported types: generic, short, stateful, pbkdf2.
     * @return callable Returns a callable function for hashing.
     * 
     * @throws CryptoException If the hashing type is unsupported.
     */
    abstract public function Hasher(string $type): callable;

    /**
     * Manages memory operations for secure data handling.
     * 
     * @param string $action The memory operation to perform.
     *                       Supported actions: clear, compare, increment.
     * @return callable Returns a callable function for memory handling.
     * 
     * @throws CryptoException If the memory action is unsupported.
     */
    abstract public function MemoryHandler(string $action): callable;

    /**
     * Handles key exchange operations between client and server.
     * 
     * @param string $type The type of key exchange operation.
     *                     Example: client, server.
     * @return callable Returns a callable function for key exchange.
     * 
     * @throws CryptoException If the key exchange type is unsupported.
     */
    abstract public function KeyExchanger(string $type): callable;

    /**
     * Resolves a dotted capability path from the driver capability map.
     */
    protected function resolveCapability(string $feature): mixed
    {
        $normalizedFeature = $this->normalizeFeatureName($feature);

        if ($normalizedFeature === '') {
            return null;
        }

        $value = $this->capabilities();

        foreach (($this->splitString('.', $normalizedFeature) ?: []) as $segment) {
            if (!$this->isArray($value) || !$this->keyExists($value, $segment)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Normalizes a feature name for consistent capability lookups.
     */
    protected function normalizeFeatureName(string $feature): string
    {
        $normalized = $this->toLower($this->trimString($feature));

        return (string) ($this->replaceByPattern('/[^a-z0-9.]+/', '', $normalized) ?? $normalized);
    }

    /**
     * Throws a framework-native crypto exception when a runtime dependency is unavailable.
     */
    protected function requireFunction(string $functionName, ?string $feature = null): string
    {
        if ($this->functionExists($functionName)) {
            return $functionName;
        }

        $label = $feature ?? $functionName;

        throw new CryptoException("Crypto runtime function is unavailable: {$label}.");
    }

    /**
     * Rejects explicit false results while allowing empty strings and zero values.
     */
    protected function rejectFalse(mixed $result, string $message): mixed
    {
        if ($result === false) {
            throw new CryptoException($message);
        }

        return $result;
    }

    /**
     * Normalizes OpenSSL-style verification results into booleans.
     */
    protected function normalizeVerificationResult(bool|int $result, string $message): bool
    {
        if ($result === false || $result === -1) {
            throw new CryptoException($message);
        }

        return $result === true || $result === 1;
    }
}
