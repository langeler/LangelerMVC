<?php

namespace App\Abstracts\Data;

use Throwable;
use App\Exceptions\Data\CryptoException;

abstract class Crypto
{
	// Symmetric (Secret-Key) Encryption / Decryption
	abstract protected function encryptWithSecretKey(string $message, string $nonce, string $key): string;
	abstract protected function decryptWithSecretKey(string $ciphertext, string $nonce, string $key): ?string;

	// Public-Key Encryption / Decryption
	abstract protected function generateKeyPair(): array;
	abstract protected function encryptWithPublicKey(string $message, string $nonce, string $publicKey, string $privateKey): string;
	abstract protected function decryptWithPrivateKey(string $ciphertext, string $nonce, string $publicKey, string $privateKey): ?string;

	// Password Hashing and Verification
	abstract protected function hashPassword(string $password, int $opslimit, int $memlimit): string;
	abstract protected function verifyPasswordHash(string $hash, string $password): bool;

	// Digital Signatures
	abstract protected function generateSignatureKeyPair(): array;
	abstract protected function signMessage(string $message, string $privateKey): string;
	abstract protected function verifyMessageSignature(string $signature, string $message, string $publicKey): bool;

	// Key Derivation
	abstract protected function deriveSubkey(int $subkeySize, int $subkeyId, string $context, string $masterKey): string;

	// Scalar Multiplication
	abstract protected function scalarMult(string $secretKey, string $publicKey): string;
	abstract protected function scalarMultBase(string $secretKey): string;

	// Hashing
	abstract protected function generateGenericHash(string $message, ?string $key = null, ?int $outputSize = null): string;
	abstract protected function generateShortHash(string $message, string $key): string;

	// AEAD
	abstract protected function encryptWithAEAD(string $message, string $nonce, string $key, string $additionalData): string;
	abstract protected function decryptWithAEAD(string $ciphertext, string $nonce, string $key, string $additionalData): ?string;

	// Random Data Generation
	abstract protected function generateRandomBytes(int $length): string;

	// Utility Functions
	abstract protected function convertBinToHex(string $data): string;
	abstract protected function convertHexToBin(string $hex): string;
	abstract protected function incrementCounter(string &$value): void;
	abstract protected function clearMemory(string &$data): void;
	abstract protected function compareMemory(string $buf1, string $buf2): bool;
}

<?php

abstract class Crypto
{
	/**
	 * Encrypts data using a secret key.
	 *
	 * @param string      $data     The data to encrypt.
	 * @param string      $key      The encryption key.
	 * @param string|null $nonce    The nonce or initialization vector (IV).
	 * @param array       $options  Additional options specific to the implementation.
	 *
	 * @return string The encrypted data.
	 *
	 * @throws CryptoException If encryption fails.
	 */
	abstract protected function encryptWithSecretKey(string $data, string $key, ?string $nonce = null, array $options = []): string;

	/**
	 * Decrypts data using a secret key.
	 *
	 * @param string      $data     The data to decrypt.
	 * @param string      $key      The decryption key.
	 * @param string|null $nonce    The nonce or initialization vector (IV).
	 * @param array       $options  Additional options specific to the implementation.
	 *
	 * @return string|null The decrypted data, or null if decryption fails.
	 *
	 * @throws CryptoException If decryption fails.
	 */
	abstract protected function decryptWithSecretKey(string $data, string $key, ?string $nonce = null, array $options = []): ?string;

	/**
	 * Encrypts data using a public key.
	 *
	 * @param string $data     The data to encrypt.
	 * @param mixed  $publicKey The public key resource or string.
	 * @param array  $options  Additional options specific to the implementation.
	 *
	 * @return string The encrypted data.
	 *
	 * @throws CryptoException If encryption fails.
	 */
	abstract protected function encryptWithPublicKey(string $data, $publicKey, array $options = []): string;

	/**
	 * Decrypts data using a private key.
	 *
	 * @param string $data      The data to decrypt.
	 * @param mixed  $privateKey The private key resource or string.
	 * @param array  $options   Additional options specific to the implementation.
	 *
	 * @return string|null The decrypted data, or null if decryption fails.
	 *
	 * @throws CryptoException If decryption fails.
	 */
	abstract protected function decryptWithPrivateKey(string $data, $privateKey, array $options = []): ?string;

	/**
	 * Signs data using a private key.
	 *
	 * @param string $data       The data to sign.
	 * @param mixed  $privateKey The private key resource or string.
	 * @param array  $options    Additional options specific to the implementation.
	 *
	 * @return string The signature.
	 *
	 * @throws CryptoException If signing fails.
	 */
	abstract protected function signMessage(string $data, $privateKey, array $options = []): string;

	/**
	 * Verifies a signature using a public key.
	 *
	 * @param string $data      The signed data.
	 * @param string $signature The signature to verify.
	 * @param mixed  $publicKey The public key resource or string.
	 * @param array  $options   Additional options specific to the implementation.
	 *
	 * @return bool True if the signature is valid, false otherwise.
	 *
	 * @throws CryptoException If verification fails.
	 */
	abstract protected function verifyMessageSignature(string $data, string $signature, $publicKey, array $options = []): bool;

	/**
	 * Generates a cryptographic hash of data.
	 *
	 * @param string $data    The data to hash.
	 * @param array  $options Additional options specific to the implementation.
	 *
	 * @return string The hash.
	 *
	 * @throws CryptoException If hashing fails.
	 */
	abstract protected function generateGenericHash(string $data, array $options = []): string;

	/**
	 * Generates a new key pair.
	 *
	 * @param array $options Additional options specific to the implementation.
	 *
	 * @return array An array containing the 'privateKey' and 'publicKey'.
	 *
	 * @throws CryptoException If key pair generation fails.
	 */
	abstract protected function generateKeyPair(array $options = []): array;

	/**
	 * Retrieves the public key from a key pair.
	 *
	 * @param mixed $key The key pair resource or string.
	 *
	 * @return string The public key.
	 *
	 * @throws CryptoException If retrieval fails.
	 */
	abstract protected function getPublicKey($key): string;

	/**
	 * Retrieves the private key from a key pair.
	 *
	 * @param mixed       $key        The key pair resource or string.
	 * @param string|null $passphrase The passphrase for the private key, if any.
	 *
	 * @return mixed The private key resource or string.
	 *
	 * @throws CryptoException If retrieval fails.
	 */
	abstract protected function getPrivateKey($key, ?string $passphrase = null);

	/**
	 * Clears sensitive data from memory.
	 *
	 * @param string &$data The data to clear.
	 *
	 * @return void
	 */
	abstract protected function clearMemory(string &$data): void;

	/**
	 * Compares two strings in constant time.
	 *
	 * @param string $a The first string.
	 * @param string $b The second string.
	 *
	 * @return bool True if the strings are equal, false otherwise.
	 *
	 * @throws CryptoException If comparison fails.
	 */
	abstract protected function compareMemory(string $a, string $b): bool;

	/**
	 * Generates cryptographically secure random bytes.
	 *
	 * @param int $length The number of bytes to generate.
	 *
	 * @return string The random bytes.
	 *
	 * @throws CryptoException If generation fails.
	 */
	abstract protected function generateRandomBytes(int $length): string;

	/**
	 * Encrypts data using AEAD cipher.
	 *
	 * @param string      $data     The data to encrypt.
	 * @param string      $key      The encryption key.
	 * @param string|null $nonce    The nonce or initialization vector (IV).
	 * @param string      $aad      Additional authenticated data.
	 * @param array       $options  Additional options specific to the implementation.
	 *
	 * @return string The encrypted data.
	 *
	 * @throws CryptoException If encryption fails.
	 */
	abstract protected function encryptWithAEAD(string $data, string $key, ?string $nonce = null, string $aad = '', array $options = []): string;

	/**
	 * Decrypts data using AEAD cipher.
	 *
	 * @param string      $data     The data to decrypt.
	 * @param string      $key      The decryption key.
	 * @param string|null $nonce    The nonce or initialization vector (IV).
	 * @param string      $aad      Additional authenticated data.
	 * @param array       $options  Additional options specific to the implementation.
	 *
	 * @return string|null The decrypted data, or null if decryption fails.
	 *
	 * @throws CryptoException If decryption fails.
	 */
	abstract protected function decryptWithAEAD(string $data, string $key, ?string $nonce = null, string $aad = '', array $options = []): ?string;

	/**
	 * Derives a subkey from a master key.
	 *
	 * @param int    $subkeySize The size of the subkey in bytes.
	 * @param int    $subkeyId   The subkey identifier.
	 * @param string $context    The context string.
	 * @param string $masterKey  The master key.
	 *
	 * @return string The derived subkey.
	 *
	 * @throws CryptoException If key derivation fails.
	 */
	abstract protected function deriveSubkey(int $subkeySize, int $subkeyId, string $context, string $masterKey): string;
}
