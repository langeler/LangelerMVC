<?php

namespace App\Utilities\Traits;

trait HashingTrait
{
	/**
	 * Generate a general-purpose hash for a string using a specified algorithm.
	 */
	public function hash(string $data, string $algorithm = 'sha256'): string
	{
		return hash($algorithm, $data);
	}

	/**
	 * Create a keyed HMAC hash for data integrity.
	 */
	public function hmac(string $data, string $key, string $algorithm = 'sha256', bool $rawOutput = false): string
	{
		return hash_hmac($algorithm, $data, $key, $rawOutput);
	}

	/**
	 * Generate a PBKDF2 hash for key derivation, often used for password-based encryption.
	 */
	public function pbkdf2(string $password, string $salt, int $iterations = 1000, int $length = 32, string $algorithm = 'sha256', bool $rawOutput = false): string
	{
		return hash_pbkdf2($algorithm, $password, $salt, $iterations, $length, $rawOutput);
	}

	/**
	 * Create a secure password hash using Argon2 (default) or Bcrypt.
	 */
	public function passwordHash(string $password, int $algo = PASSWORD_ARGON2ID): string
	{
		return password_hash($password, $algo);
	}

	/**
	 * Verify a password against a hashed password.
	 */
	public function verifyPassword(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}

	/**
	 * Securely compare two strings or hashes to prevent timing attacks.
	 */
	public function compare(string $known, string $userInput): bool
	{
		return hash_equals($known, $userInput);
	}

	/**
	 * Retrieve a list of all hashing algorithms supported by PHP.
	 */
	public function getAvailableAlgorithms(): array
	{
		return hash_algos();
	}
}
