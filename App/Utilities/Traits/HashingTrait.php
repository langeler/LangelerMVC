<?php

namespace App\Utilities\Traits;

/**
 * Trait HashingTrait
 *
 * Provides utility methods for hashing, key derivation, and secure string operations.
 * Includes all PHP `hash_*` methods for comprehensive support.
 */
trait HashingTrait
{
	/**
	 * Generate a general-purpose hash for a string using a specified algorithm.
	 *
	 * @param string $data       The input string to hash.
	 * @param string $algorithm  The hashing algorithm (default: 'sha256').
	 * @return string            The hashed string.
	 */
	public function hash(string $data, string $algorithm = 'sha256'): string
	{
		return hash($algorithm, $data);
	}

	/**
	 * Create a keyed HMAC hash for data integrity.
	 *
	 * @param string $data        The input string to hash.
	 * @param string $key         The key for the HMAC hash.
	 * @param string $algorithm   The hashing algorithm (default: 'sha256').
	 * @param bool $rawOutput     Whether to return raw binary data (default: false).
	 * @return string             The HMAC hash.
	 */
	public function hmac(string $data, string $key, string $algorithm = 'sha256', bool $rawOutput = false): string
	{
		return hash_hmac($algorithm, $data, $key, $rawOutput);
	}

	/**
	 * Generate a PBKDF2 hash for key derivation, often used for password-based encryption.
	 *
	 * @param string $password     The input password.
	 * @param string $salt         The salt for the hash.
	 * @param int $iterations      The number of iterations (default: 1000).
	 * @param int $length          The desired hash length (default: 32).
	 * @param string $algorithm    The hashing algorithm (default: 'sha256').
	 * @param bool $rawOutput      Whether to return raw binary data (default: false).
	 * @return string              The PBKDF2 hash.
	 */
	public function pbkdf2(string $password, string $salt, int $iterations = 1000, int $length = 32, string $algorithm = 'sha256', bool $rawOutput = false): string
	{
		return hash_pbkdf2($algorithm, $password, $salt, $iterations, $length, $rawOutput);
	}

	/**
	 * Create a secure password hash using Argon2 (default) or Bcrypt.
	 *
	 * @param string $password The input password.
	 * @param int $algo        The hashing algorithm (default: PASSWORD_ARGON2ID).
	 * @return string          The hashed password.
	 */
	public function passwordHash(string $password, int $algo = PASSWORD_ARGON2ID): string
	{
		return password_hash($password, $algo);
	}

	/**
	 * Verify a password against a hashed password.
	 *
	 * @param string $password The input password.
	 * @param string $hash     The hashed password.
	 * @return bool            True if the password matches, false otherwise.
	 */
	public function verifyPassword(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}

	/**
	 * Securely compare two strings or hashes to prevent timing attacks.
	 *
	 * @param string $known     The known string (e.g., a stored hash).
	 * @param string $userInput The user input string.
	 * @return bool             True if the strings are equal, false otherwise.
	 */
	public function compare(string $known, string $userInput): bool
	{
		return hash_equals($known, $userInput);
	}

	/**
	 * Retrieve a list of all hashing algorithms supported by PHP.
	 *
	 * @return array The list of supported hashing algorithms.
	 */
	public function getAvailableAlgorithms(): array
	{
		return hash_algos();
	}

	/**
	 * Generate a hash for a file using the specified algorithm.
	 *
	 * @param string $filename    The path to the file.
	 * @param string $algorithm   The hashing algorithm (default: 'sha256').
	 * @param bool $rawOutput     Whether to return raw binary data (default: false).
	 * @return string|false       The hashed file contents, or false on failure.
	 */
	public function hashFile(string $filename, string $algorithm = 'sha256', bool $rawOutput = false): string|false
	{
		return hash_file($algorithm, $filename, $rawOutput);
	}

	/**
	 * Generate a keyed HMAC hash for a file.
	 *
	 * @param string $filename    The path to the file.
	 * @param string $key         The key for the HMAC hash.
	 * @param string $algorithm   The hashing algorithm (default: 'sha256').
	 * @param bool $rawOutput     Whether to return raw binary data (default: false).
	 * @return string|false       The HMAC hash of the file contents, or false on failure.
	 */
	public function hmacFile(string $filename, string $key, string $algorithm = 'sha256', bool $rawOutput = false): string|false
	{
		return hash_hmac_file($algorithm, $filename, $key, $rawOutput);
	}

	/**
	 * Retrieve information about the hash state for a given algorithm.
	 *
	 * @param string $algorithm The hashing algorithm.
	 * @return array            An array containing information about the hash state.
	 */
	public function getHashState(string $algorithm): array
	{
		return hash_init($algorithm)->getState();
	}

	/**
	 * Compute a rolling hash using the Adler-32 or CRC32 algorithm.
	 *
	 * @param string $data       The input data.
	 * @param int $type          The rolling hash algorithm (HASH_ADLER32 or HASH_CRC32).
	 * @param int $state         The initial hash state (default: 0).
	 * @return int               The rolling hash value.
	 */
	public function computeRollingHash(string $data, int $type, int $state = 0): int
	{
		return hash_update($type, $data, $state);
	}
}
