<?php

namespace App\Utilities\Handlers;

/**
 * Class CryptoHandler
 *
 * Provides utility methods for encryption, decryption, hashing, and cryptographic operations using Sodium, OpenSSL, and Hashing functions.
 */
class CryptoHandler
{
	// Sodium Methods

	/**
	 * Encrypt data using Sodium.
	 *
	 * @param string $plaintext The plaintext data.
	 * @param string $key The encryption key.
	 * @return string The encrypted data, with the nonce prepended.
	 * @throws \Exception If the key length is not valid.
	 */
	public function sodiumEncrypt(string $plaintext, string $key): string
	{
		if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			throw new \Exception("Key must be exactly " . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . " bytes.");
		}

		$nonce = $this->generateNonce();  // Generate a 24-byte nonce
		return $nonce . sodium_crypto_secretbox($plaintext, $nonce, $key);  // Prepend nonce to the ciphertext
	}

	/**
	 * Decrypt data using Sodium.
	 *
	 * @param string $ciphertext The encrypted data (with the nonce prepended).
	 * @param string $key The decryption key.
	 * @return string The decrypted data.
	 * @throws \SodiumException If the nonce or key is not valid.
	 */
	public function sodiumDecrypt(string $ciphertext, string $key): string
	{
		if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			throw new \SodiumException("Key must be exactly " . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . " bytes.");
		}

		$nonce = substr($ciphertext, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);  // Extract the nonce from the ciphertext
		$ciphertext = substr($ciphertext, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);  // The actual ciphertext
		$plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

		if ($plaintext === false) {
			throw new \SodiumException("Decryption failed. Invalid key or corrupted data.");
		}

		return $plaintext;
	}

	/**
	 * Generate a nonce for Sodium encryption.
	 *
	 * @param int $length The length of the nonce (default is 24 bytes for sodium).
	 * @return string The generated nonce.
	 */
	public function generateNonce(int $length = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES): string
	{
		return random_bytes($length);  // Ensure the nonce is exactly 24 bytes
	}

	/**
	 * Sign data using Sodium.
	 *
	 * @param string $message The message to sign.
	 * @param string $secretKey The signing key.
	 * @return string The signed message.
	 */
	public function sodiumSign(string $message, string $secretKey): string
	{
		return sodium_crypto_sign($message, $secretKey);
	}

	/**
	 * Verify a signed message using Sodium.
	 *
	 * @param string $signedMessage The signed message.
	 * @param string $publicKey The public key for verification.
	 * @return string|false The original message or false on failure.
	 */
	public function sodiumVerify(string $signedMessage, string $publicKey)
	{
		return sodium_crypto_sign_open($signedMessage, $publicKey);
	}

	/**
	 * Encrypt data using Sodium Box.
	 *
	 * @param string $message The message to encrypt.
	 * @param string $nonce The nonce value.
	 * @param string $keyPair The key pair for encryption.
	 * @return string The encrypted message.
	 */
	public function sodiumBoxEncrypt(string $message, string $nonce, string $keyPair): string
	{
		return sodium_crypto_box($message, $nonce, $keyPair);
	}

	/**
	 * Decrypt data using Sodium Box.
	 *
	 * @param string $ciphertext The encrypted message.
	 * @param string $nonce The nonce value.
	 * @param string $keyPair The key pair for decryption.
	 * @return string|false The decrypted message or false on failure.
	 */
	public function sodiumBoxDecrypt(string $ciphertext, string $nonce, string $keyPair)
	{
		return sodium_crypto_box_open($ciphertext, $nonce, $keyPair);
	}

	/**
	 * Generate a generic hash using Sodium.
	 *
	 * @param string $message The message to hash.
	 * @param string|null $key The key for keyed hashing (optional).
	 * @param int $length The length of the output hash.
	 * @return string The generated hash.
	 */
	public function sodiumGenericHash(string $message, ?string $key = null, int $length = SODIUM_CRYPTO_GENERICHASH_BYTES): string
	{
		return sodium_crypto_generichash($message, $key, $length);
	}

	/**
	 * Hash a password using Sodium.
	 *
	 * @param int $length The length of the output hash.
	 * @param string $password The password to hash.
	 * @param string $salt The salt value.
	 * @param int $opslimit The operations limit.
	 * @param int $memlimit The memory limit.
	 * @param int $algo The algorithm identifier.
	 * @return string The hashed password.
	 */
	public function sodiumPasswordHash(int $length, string $password, string $salt, int $opslimit, int $memlimit, int $algo = SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13): string
	{
		return sodium_crypto_pwhash($length, $password, $salt, $opslimit, $memlimit, $algo);
	}

	/**
	 * Hash a password using the Sodium built-in password hashing method.
	 *
	 * @param string $password The password to hash.
	 * @param int $opslimit The operations limit.
	 * @param int $memlimit The memory limit.
	 * @return string The hashed password.
	 */
	public function sodiumPasswordHashStr(string $password, int $opslimit, int $memlimit): string
	{
		return sodium_crypto_pwhash_str($password, $opslimit, $memlimit);
	}

	/**
	 * Verify a password against a hashed value using Sodium.
	 *
	 * @param string $hashedPassword The hashed password.
	 * @param string $password The plaintext password.
	 * @return bool True if the password matches the hash, false otherwise.
	 */
	public function sodiumPasswordHashVerify(string $hashedPassword, string $password): bool
	{
		return sodium_crypto_pwhash_str_verify($hashedPassword, $password);
	}

	/**
	 * Generate random bytes using Sodium.
	 *
	 * @param int $length The number of bytes to generate.
	 * @return string The generated random bytes.
	 */
	public function sodiumRandomBytes(int $length): string
	{
		return random_bytes($length);  // Replaced with PHP's random_bytes()
	}

	/**
	 * Generate a random number using Sodium.
	 *
	 * @param int $upperBound The upper bound for the random number.
	 * @return int The generated random number.
	 */
	public function sodiumRandomNumber(int $upperBound): int
	{
		return sodium_randombytes_uniform($upperBound);
	}

	// OpenSSL Methods

	/**
	 * Get the IV length for a cipher method.
	 *
	 * @param string $cipherMethod The cipher method.
	 * @return int The IV length.
	 */
	public function getIvLength(string $cipherMethod): int
	{
		return openssl_cipher_iv_length($cipherMethod);
	}

	/**
	 * Encrypt data using OpenSSL.
	 *
	 * @param string $data The data to encrypt.
	 * @param string $method The encryption method.
	 * @param string $password The encryption password.
	 * @param int $options The options for encryption.
	 * @param string $iv The initialization vector.
	 * @param string|null $tag The authentication tag (GCM mode).
	 * @param string $aad Additional authenticated data (GCM mode).
	 * @return string|false The encrypted data or false on failure.
	 */
	public function encryptData(string $data, string $method, string $password, int $options = 0, string $iv = '', ?string &$tag = null, string $aad = '')
	{
		return openssl_encrypt($data, $method, $password, $options, $iv, $tag, $aad);
	}

	/**
	 * Decrypt data using OpenSSL.
	 *
	 * @param string $data The data to decrypt.
	 * @param string $method The decryption method.
	 * @param string $password The decryption password.
	 * @param int $options The options for decryption.
	 * @param string $iv The initialization vector.
	 * @param string|null $tag The authentication tag (GCM mode).
	 * @param string $aad Additional authenticated data (GCM mode).
	 * @return string|false The decrypted data or false on failure.
	 */
	public function decryptData(string $data, string $method, string $password, int $options = 0, string $iv = '', ?string $tag = null, string $aad = '')
	{
		return openssl_decrypt($data, $method, $password, $options, $iv, $tag, $aad);
	}

	/**
	 * Sign data using a private key and OpenSSL.
	 *
	 * @param string $data The data to sign.
	 * @param string &$signature The generated signature.
	 * @param string $privateKey The private key used to sign the data.
	 * @param int $algo The signature algorithm.
	 * @return bool True on success, false on failure.
	 */
	public function signData(string $data, &$signature, string $privateKey, int $algo = OPENSSL_ALGO_SHA256): bool
	{
		return openssl_sign($data, $signature, $privateKey, $algo);
	}

	/**
	 * Verify a signature using a public key and OpenSSL.
	 *
	 * @param string $data The data to verify.
	 * @param string $signature The signature to verify.
	 * @param string $publicKey The public key used for verification.
	 * @param int $algo The signature algorithm.
	 * @return bool True if the signature is valid, false otherwise.
	 */
	public function verifySignature(string $data, string $signature, string $publicKey, int $algo = OPENSSL_ALGO_SHA256): bool
	{
		return openssl_verify($data, $signature, $publicKey, $algo) === 1;
	}

	/**
	 * Generate a pair of private and public keys using OpenSSL.
	 *
	 * @param array $configArgs The configuration options.
	 * @return resource The generated key pair.
	 */
	public function generateKeyPair(array $configArgs = [])
	{
		return openssl_pkey_new($configArgs);
	}

	/**
	 * Export a private key using OpenSSL.
	 *
	 * @param resource $key The key resource.
	 * @param string &$out The output where the key is exported.
	 * @param string|null $passphrase The passphrase for the key (optional).
	 * @param array $configArgs Additional configuration options.
	 * @return bool True on success, false on failure.
	 */
	public function exportPrivateKey($key, &$out, ?string $passphrase = null, array $configArgs = []): bool
	{
		return openssl_pkey_export($key, $out, $passphrase, $configArgs);
	}

	/**
	 * Get details about a key using OpenSSL.
	 *
	 * @param resource $key The key resource.
	 * @return array|false The key details or false on failure.
	 */
	public function getKeyDetails($key)
	{
		return openssl_pkey_get_details($key);
	}

	/**
	 * Generate a random string using OpenSSL.
	 *
	 * @param int $length The length of the string.
	 * @param bool &$cryptoStrong If true, indicates the string is cryptographically strong.
	 * @return string The generated string.
	 */
	public function generatePseudoRandomBytes(int $length, ?bool &$cryptoStrong = null): string
	{
		return openssl_random_pseudo_bytes($length, $cryptoStrong);
	}

	// Hashing Methods

	/**
	 * Generate a hash of a given data using a specific algorithm.
	 *
	 * @param string $algo The hashing algorithm.
	 * @param string $data The data to hash.
	 * @param array $options Additional options for the hash function.
	 * @return string The hashed data.
	 */
	public function hashData(string $algo, string $data, array $options = []): string
	{
		return hash($algo, $data, $options);
	}

	/**
	 * Generate a hash of a given file using a specific algorithm.
	 *
	 * @param string $algo The hashing algorithm.
	 * @param string $filename The path to the file.
	 * @param array $options Additional options for the hash function.
	 * @return string The file's hashed data.
	 */
	public function hashFile(string $algo, string $filename, array $options = []): string
	{
		return hash_file($algo, $filename, $options);
	}

	/**
	 * Compare two hashed values in a time-constant manner.
	 *
	 * @param string $knownString The known, good hash.
	 * @param string $userString The user-supplied hash to check.
	 * @return bool True if the strings are equal, false otherwise.
	 */
	public function compareHash(string $knownString, string $userString): bool
	{
		return hash_equals($knownString, $userString);
	}

	/**
	 * Initialize a hashing context.
	 *
	 * @param string $algo The hashing algorithm.
	 * @param int $options Options for the hashing algorithm.
	 * @param string|null $key A key for keyed hashing.
	 * @return resource The initialized hashing context.
	 */
	public function initHash(string $algo, int $options = 0, ?string $key = null)
	{
		return hash_init($algo, $options, $key);
	}

	/**
	 * Update a hashing context with more data.
	 *
	 * @param resource $context The hashing context.
	 * @param string $data The data to add to the context.
	 * @return bool True on success, false on failure.
	 */
	public function updateHash($context, string $data): bool
	{
		return hash_update($context, $data);
	}

	/**
	 * Finalize a hash and return the result.
	 *
	 * @param resource $context The hashing context.
	 * @param bool $rawOutput Whether to return raw binary data.
	 * @return string The finalized hash.
	 */
	public function finalizeHash($context, bool $rawOutput = false): string
	{
		return hash_final($context, $rawOutput);
	}

	/**
	 * Generate a keyed hash using HMAC.
	 *
	 * @param string $algo The hashing algorithm.
	 * @param string $data The data to hash.
	 * @param string $key The key for the HMAC.
	 * @param bool $rawOutput Whether to return raw binary data.
	 * @return string The generated HMAC.
	 */
	public function hmacHash(string $algo, string $data, string $key, bool $rawOutput = false): string
	{
		return hash_hmac($algo, $data, $key, $rawOutput);
	}

	/**
	 * Generate a PBKDF2 hash.
	 *
	 * @param string $algo The hashing algorithm.
	 * @param string $password The password to hash.
	 * @param string $salt The salt for the hash.
	 * @param int $iterations The number of iterations.
	 * @param int $length The length of the derived key.
	 * @param bool $rawOutput Whether to return raw binary data.
	 * @return string The derived key.
	 */
	public function pbkdf2Hash(string $algo, string $password, string $salt, int $iterations, int $length = 0, bool $rawOutput = false): string
	{
		return hash_pbkdf2($algo, $password, $salt, $iterations, $length, $rawOutput);
	}

	/**
	 * Get a list of available hashing algorithms.
	 *
	 * @return array The list of available algorithms.
	 */
	public function availableHashes(): array
	{
		return hash_algos();
	}

	/**
	 * Get a list of available HMAC hashing algorithms.
	 *
	 * @return array The list of available HMAC algorithms.
	 */
	public function availableHmacHashes(): array
	{
		return hash_hmac_algos();
	}
}
