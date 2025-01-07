<?php

namespace App\Contracts\Data;

/**
 * Interface defining a contract for cryptographic operations.
 * 
 * This interface ensures that implementing classes provide
 * consistent cryptographic methods for encryption, decryption,
 * hashing, random generation, and key management.
 */
interface CryptoInterface
{
    /**
     * Perform data conversions such as base64 encoding, hex conversions, etc.
     * 
     * @param string $type The type of data conversion to perform.
     *                     Example: bin2base64, base642bin, bin2hex, hex2bin.
     * @return callable Returns a callable function for the specified conversion.
     */
    public function DataConverter(string $type): callable;

    /**
     * Handles encryption operations.
     * 
     * @param string $type The type of encryption to perform.
     *                     Example: symmetric, asymmetric, AEAD.
     * @return callable Returns a callable function for encryption.
     */
    public function Encryptor(string $type): callable;

    /**
     * Handles decryption operations.
     * 
     * @param string $type The type of decryption to perform.
     *                     Example: symmetric, asymmetric, AEAD.
     * @return callable Returns a callable function for decryption.
     */
    public function Decryptor(string $type): callable;

    /**
     * Generates secure random bytes for cryptographic purposes.
     * 
     * @param string $type The type of random data to generate.
     *                     Example: default, passwordSalt, scalar.
     * @param int|null $length Optional length of the random data in bytes.
     * @return callable Returns a callable function for random byte generation.
     */
    public function RandomGenerator(string $type, ?int $length = null): callable;

    /**
     * Provides hashing capabilities.
     * 
     * @param string $type The type of hashing operation to perform.
     *                     Supported types: generic, short, pbkdf2.
     * @return callable Returns a callable function for hashing.
     */
    public function Hasher(string $type): callable;

    /**
     * Securely handles memory operations.
     * 
     * @param string $action The memory operation to perform.
     *                       Example: clear, compare, increment.
     * @return callable Returns a callable function for memory handling.
     */
    public function MemoryHandler(string $action): callable;

    /**
     * Handles key exchange operations.
     * 
     * @param string $type The type of key exchange operation.
     *                     Example: client, server.
     * @return callable Returns a callable function for key exchange.
     */
    public function KeyExchanger(string $type): callable;
}