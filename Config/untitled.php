<?php

protected function MemoryHandler(string $action): callable
{
	return match ($action) {
		'clear' => fn(string &$data) => $data = str_repeat("\0", strlen($data)),
		'compare' => fn(string $a, string $b) => hash_equals($a, $b),
		default => throw new CryptoException("Unsupported memory action: {$action}."),
	};
}

protected function RandomGenerator(string $type, int $length = 32): callable
{
	return match ($type) {
		'default' => fn() => random_bytes($length),
		'passwordSalt' => fn() => random_bytes($this->config['pwHash']['saltBytes'] ?? 16),
		'iv' => fn(string $cipher) => random_bytes(openssl_cipher_iv_length($cipher)),
		default => throw new CryptoException("Unsupported random generation type: {$type}."),
	};
}

protected function PKCS7Handler(string $action): callable
{
	return match ($action) {
		'encrypt' => fn(string $data, array $certs, array $flags = []) =>
			openssl_pkcs7_encrypt(
				$data,
				'php://memory',
				$certs,
				[],
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? PKCS7_BINARY),
					PKCS7_BINARY
				)
			) ?: throw new CryptoException("PKCS#7 encryption failed."),

		'decrypt' => fn(string $data, string $certPath, string $keyPath, array $flags = []) =>
			openssl_pkcs7_decrypt(
				$data,
				'php://memory',
				$certPath,
				$keyPath,
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? 0),
					0
				)
			) ?: throw new CryptoException("PKCS#7 decryption failed."),

		'sign' => fn(string $data, string $certPath, string $keyPath, array $flags = []) =>
			openssl_pkcs7_sign(
				$data,
				'php://memory',
				$certPath,
				$keyPath,
				[],
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? PKCS7_DETACHED),
					PKCS7_DETACHED
				)
			) ?: throw new CryptoException("PKCS#7 signing failed."),

		'verify' => fn(string $data, string $certPath, array $flags = []) =>
			openssl_pkcs7_verify(
				$data,
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? PKCS7_NOVERIFY),
					PKCS7_NOVERIFY
				),
				'php://memory',
				[$certPath]
			) ?: throw new CryptoException("PKCS#7 verification failed."),

		default => throw new CryptoException("Unsupported PKCS#7 action: {$action}."),
	};
}

protected function CMSHandler(string $action): callable
{
	return match ($action) {
		'encrypt' => fn(string $data, array $certs, string $cipher = 'aes256cbc', array $flags = []) =>
			openssl_cms_encrypt(
				$data,
				'php://memory',
				$certs,
				$this->config['ciphers'][$cipher] ?? OPENSSL_CIPHER_AES_256_CBC,
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['cms'][$flag] ?? 0),
					0
				)
			) ?: throw new CryptoException("CMS encryption failed."),

		'decrypt' => fn(string $data, string $certPath, string $keyPath, array $flags = []) =>
			openssl_cms_decrypt(
				$data,
				$certPath,
				$keyPath,
				array_reduce(
					$flags,
					fn($carry, $flag) => $carry | ($this->config['cms'][$flag] ?? 0),
					0
				)
			) ?: throw new CryptoException("CMS decryption failed."),

		default => throw new CryptoException("Unsupported CMS action: {$action}."),
	};
}

protected function HMACHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn(string $data, string $key, string $algorithm = 'sha256') =>
			hash_hmac(
				$this->config['algorithms'][$algorithm] ?? 'sha256',
				$data,
				$key,
				true
			) ?: throw new CryptoException("HMAC generation failed."),

		'verify' => fn(string $data, string $expectedHMAC, string $key, string $algorithm = 'sha256') =>
			$this->MemoryHandler('compare')(
				hash_hmac(
					$this->config['algorithms'][$algorithm] ?? 'sha256',
					$data,
					$key,
					true
				),
				$expectedHMAC
			) ?: throw new CryptoException("HMAC verification failed."),

		default => throw new CryptoException("Unsupported HMAC action: {$action}."),
	};
}

protected function SignHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn(string $data, $privateKey, string $algorithm = 'sha256') =>
			openssl_sign(
				$data,
				$signature,
				$privateKey,
				$this->config['algorithms'][$algorithm] ?? OPENSSL_ALGO_SHA256
			) ? $signature : throw new CryptoException("Signing failed."),

		'verify' => fn(string $data, string $signature, $publicKey, string $algorithm = 'sha256') =>
			openssl_verify(
				$data,
				$signature,
				$publicKey,
				$this->config['algorithms'][$algorithm] ?? OPENSSL_ALGO_SHA256
			) === 1 ?: throw new CryptoException("Signature verification failed."),

		'generateDetached' => fn(string $data, $privateKey, string $algorithm = 'sha256') =>
			openssl_sign(
				$data,
				$signature,
				$privateKey,
				$this->config['algorithms'][$algorithm] ?? OPENSSL_ALGO_SHA256
			) ? $signature : throw new CryptoException("Detached signature generation failed."),

		default => throw new CryptoException("Unsupported signing action: {$action}."),
	};
}

protected function HashHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn(string $data, string $algorithm = 'sha256') =>
			openssl_digest(
				$data,
				$this->config['algorithms'][$algorithm] ?? 'sha256',
				true
			) ?: throw new CryptoException("Hash generation failed."),

		'verify' => fn(string $data, string $expectedHash, string $algorithm = 'sha256') =>
			$this->MemoryHandler('compare')(
				openssl_digest($data, $this->config['algorithms'][$algorithm] ?? 'sha256', true),
				$expectedHash
			) ?: throw new CryptoException("Hash verification failed."),

		'stateInit' => fn(string $algorithm = 'sha256') =>
			openssl_digest_init(
				$this->config['algorithms'][$algorithm] ?? OPENSSL_ALGO_SHA256
			) ?: throw new CryptoException("Hash state initialization failed."),

		'stateUpdate' => fn($state, string $data) =>
			openssl_digest_update($state, $data) ?: throw new CryptoException("Updating hash state failed."),

		'stateFinalize' => fn($state) =>
			openssl_digest_final($state, true) ?: throw new CryptoException("Finalizing hash state failed."),

		default => throw new CryptoException("Unsupported hash action: {$action}."),
	};
}

protected function RandomHandler(string $type): callable
{
	return match ($type) {
		'generate' => fn(int $length) =>
			random_bytes($length) ?: throw new CryptoException("Random bytes generation failed."),

		'salt' => fn(int $length = 32) =>
			random_bytes($length) ?: throw new CryptoException("Salt generation failed."),

		default => throw new CryptoException("Unsupported random bytes type: {$type}."),
	};
}

protected function MemoryHandler(string $action): callable
{
	return match ($action) {
		'compare' => fn(string $a, string $b) =>
			hash_equals($a, $b) ?: throw new CryptoException("Memory comparison failed."),

		'clear' => fn(string &$data) =>
			$data = str_repeat("\0", strlen($data)) ?: throw new CryptoException("Clearing memory failed."),

		default => throw new CryptoException("Unsupported memory action: {$action}."),
	};
}

protected function SPKIHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn($privateKey, string $challenge) =>
			openssl_spki_new($privateKey, $challenge) ?: throw new CryptoException("SPKI generation failed."),

		'verify' => fn(string $spki) =>
			openssl_spki_verify($spki) ?: throw new CryptoException("SPKI verification failed."),

		'export' => fn(string $spki) =>
			openssl_spki_export($spki) ?: throw new CryptoException("SPKI export failed."),

		'exportChallenge' => fn(string $spki) =>
			openssl_spki_export_challenge($spki) ?: throw new CryptoException("SPKI challenge export failed."),

		default => throw new CryptoException("Unsupported SPKI action: {$action}."),
	};
}

protected function KeyExchangeHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn(array $configArgs = []) =>
			openssl_pkey_new($this->prepareKeyConfig($configArgs)) ?: throw new CryptoException("Key exchange generation failed."),

		'exportPrivate' => fn($privateKey) =>
			openssl_pkey_export($privateKey, $output) ? $output : throw new CryptoException("Exporting private key failed."),

		'getDetails' => fn($key) =>
			openssl_pkey_get_details($key) ?: throw new CryptoException("Getting key details failed."),

		'derive' => fn($peerPublicKey, $privateKey) =>
			openssl_pkey_derive($peerPublicKey, $privateKey) ?: throw new CryptoException("Key derivation failed."),

		default => throw new CryptoException("Unsupported key exchange action: {$action}."),
	};
}

private function prepareKeyConfig(array $configArgs): array
{
	return [
		'private_key_type' => $configArgs['private_key_type'] ?? $this->config['keyTypes']['rsa'] ?? OPENSSL_KEYTYPE_RSA,
		'private_key_bits' => $configArgs['private_key_bits'] ?? 2048,
	] + $configArgs;
}

protected function PKCS7Handler(string $action): callable
{
	return match ($action) {
		'encrypt' => fn(string $data, array $certs, array $flags = []) =>
			openssl_pkcs7_encrypt(
				$this->createTemporaryFile($data),
				$outputFile = $this->createTemporaryFile(),
				$certs,
				[],
				$this->combineFlags('pkcs7', $flags)
			) ? file_get_contents($outputFile) : throw new CryptoException("PKCS7 encryption failed."),

		'decrypt' => fn(string $data, string $cert, string $key, array $flags = []) =>
			openssl_pkcs7_decrypt(
				$this->createTemporaryFile($data),
				$outputFile = $this->createTemporaryFile(),
				$cert,
				$key
			) ? file_get_contents($outputFile) : throw new CryptoException("PKCS7 decryption failed."),

		'sign' => fn(string $data, string $cert, string $key, array $flags = []) =>
			openssl_pkcs7_sign(
				$this->createTemporaryFile($data),
				$outputFile = $this->createTemporaryFile(),
				$cert,
				$key,
				[],
				$this->combineFlags('pkcs7', $flags)
			) ? file_get_contents($outputFile) : throw new CryptoException("PKCS7 signing failed."),

		'verify' => fn(string $data, string $cert, array $flags = []) =>
			openssl_pkcs7_verify(
				$this->createTemporaryFile($data),
				$this->combineFlags('pkcs7', $flags),
				$outputFile = $this->createTemporaryFile(),
				[$cert]
			) ?: throw new CryptoException("PKCS7 verification failed."),

		default => throw new CryptoException("Unsupported PKCS7 action: {$action}."),
	};
}

private function createTemporaryFile(?string $data = null): string
{
	$file = tempnam(sys_get_temp_dir(), 'crypto_tmp_');
	if ($data !== null) {
		file_put_contents($file, $data);
	}
	return $file;
}

protected function CMSHandler(string $action): callable
{
	return match ($action) {
		'encrypt' => fn(string $data, array $certs, string $cipher = 'aes256cbc', array $flags = []) =>
			openssl_cms_encrypt(
				$data,
				'php://memory',
				$certs,
				[$this->config['ciphers'][$cipher] ?? OPENSSL_CIPHER_AES_256_CBC],
				$this->combineFlags('cms', $flags)
			) ?: throw new CryptoException("CMS encryption failed."),

		'decrypt' => fn(string $data, string $cert, string $key, array $flags = []) =>
			openssl_cms_decrypt(
				$data,
				$cert,
				$key,
				$this->combineFlags('cms', $flags)
			) ?: throw new CryptoException("CMS decryption failed."),

		'sign' => fn(string $data, string $cert, string $privateKey, array $flags = []) =>
			openssl_cms_sign(
				$data,
				'php://memory',
				$cert,
				$privateKey,
				[],
				$this->combineFlags('cms', $flags)
			) ?: throw new CryptoException("CMS signing failed."),

		'verify' => fn(string $data, string $cert, array $flags = []) =>
			openssl_cms_verify(
				$data,
				$this->combineFlags('cms', $flags),
				null,
				[$cert]
			) ?: throw new CryptoException("CMS verification failed."),

		default => throw new CryptoException("Unsupported CMS action: {$action}."),
	};
}

private function combineFlags(string $type, array $flags): int
{
	return array_reduce(
		$flags,
		fn($carry, $flag) => $carry | ($this->config[$type][$flag] ?? 0),
		0
	);
}

protected function CertificateHandler(string $action): callable
{
	return match ($action) {
		'parse' => fn($certificate) =>
			openssl_x509_parse($certificate)
			?: throw new CryptoException("Parsing certificate failed."),

		'export' => fn($certificate) =>
			openssl_x509_export($certificate, $output)
			? $output
			: throw new CryptoException("Certificate export failed."),

		'verifyPrivateKey' => fn($certificate, $privateKey) =>
			openssl_x509_check_private_key($certificate, $privateKey)
			?: throw new CryptoException("Private key does not correspond to the certificate."),

		'getFingerprint' => fn($certificate, $algo = 'sha256') =>
			openssl_x509_fingerprint($certificate, $algo, true)
			?: throw new CryptoException("Getting certificate fingerprint failed."),

		default => throw new CryptoException("Unsupported certificate action: {$action}."),
	};
}

protected function CSRHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn(array $dn, $privateKey, array $config = []) =>
			openssl_csr_new($dn, $privateKey, $config)
			?: throw new CryptoException("CSR generation failed."),

		'export' => fn($csr) =>
			openssl_csr_export($csr, $output)
			? $output
			: throw new CryptoException("CSR export failed."),

		'getPublicKey' => fn($csr) =>
			openssl_csr_get_public_key($csr)
			?: throw new CryptoException("Getting CSR public key failed."),

		'getSubject' => fn($csr) =>
			openssl_csr_get_subject($csr)
			?: throw new CryptoException("Getting CSR subject failed."),

		default => throw new CryptoException("Unsupported CSR action: {$action}."),
	};
}

protected function KeyGenerator(string $type): callable
{
	return match ($type) {
		'rsa' => fn($keyBits = 2048, array $config = []) =>
			openssl_pkey_new(array_merge($config, [
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
				'private_key_bits' => $keyBits,
			])) ?: throw new CryptoException("RSA keypair generation failed."),

		'ec' => fn($curve = 'prime256v1', array $config = []) =>
			openssl_pkey_new(array_merge($config, [
				'private_key_type' => OPENSSL_KEYTYPE_EC,
				'curve_name' => $curve,
			])) ?: throw new CryptoException("EC keypair generation failed."),

		'dsa' => fn($keyBits = 1024, array $config = []) =>
			openssl_pkey_new(array_merge($config, [
				'private_key_type' => OPENSSL_KEYTYPE_DSA,
				'private_key_bits' => $keyBits,
			])) ?: throw new CryptoException("DSA keypair generation failed."),

		default => throw new CryptoException("Unsupported key type: {$type}."),
	};
}

protected function KeyExporter(string $type): callable
{
	return match ($type) {
		'private' => fn($key, ?string $passphrase = null) =>
			openssl_pkey_export($key, $output, $passphrase)
			? $output
			: throw new CryptoException("Private key export failed."),

		'public' => fn($key) =>
			openssl_pkey_get_details($key)['key']
			?? throw new CryptoException("Public key export failed."),

		default => throw new CryptoException("Unsupported key export type: {$type}."),
	};
}

protected function KeyImporter(string $type): callable
{
	return match ($type) {
		'private' => fn($key, ?string $passphrase = null) =>
			openssl_pkey_get_private($key, $passphrase)
			?: throw new CryptoException("Private key import failed."),

		'public' => fn($key) =>
			openssl_pkey_get_public($key)
			?: throw new CryptoException("Public key import failed."),

		default => throw new CryptoException("Unsupported key import type: {$type}."),
	};
}

protected function KeyFreeer(): callable
{
	return fn($key) =>
		openssl_pkey_free($key)
		?: throw new CryptoException("Freeing key failed.");
}

protected function SPKIHandler(string $action): callable
{
	return match ($action) {
		'generate' => fn($privateKey, $challenge) =>
			openssl_spki_new($privateKey, $challenge)
			?: throw new CryptoException("SPKI generation failed."),

		'verify' => fn($spki) =>
			openssl_spki_verify($spki)
			?: throw new CryptoException("SPKI verification failed."),

		'export' => fn($spki) =>
			openssl_spki_export($spki)
			?: throw new CryptoException("SPKI export failed."),

		'exportChallenge' => fn($spki) =>
			openssl_spki_export_challenge($spki)
			?: throw new CryptoException("SPKI challenge export failed."),

		default => throw new CryptoException("Unsupported SPKI action: {$action}."),
	};
}

protected function MemoryHandler(string $action): callable
{
	return match ($action) {
		'clear' => fn(&$data) =>
			$data = str_repeat("\0", strlen($data)),

		'compare' => fn($a, $b) =>
			hash_equals($a, $b),

		default => throw new CryptoException("Unsupported memory action: {$action}."),
	};
}

protected function RandomGenerator(string $type): callable
{
	return match ($type) {
		'default' => fn($length) =>
			random_bytes($length ?? ($this->config['random']['defaultLength'] ?? 32)),

		'passwordSalt' => fn($length) =>
			random_bytes($length ?? ($this->config['pwHash']['saltLength'] ?? 16)),

		'iv' => fn($cipher) =>
			random_bytes(openssl_cipher_iv_length($this->config['ciphers'][$cipher] ?? 'aes-256-cbc')),

		'key' => fn($length) =>
			random_bytes($length ?? ($this->config['keys']['defaultLength'] ?? 32)),

		default => throw new CryptoException("Unsupported random byte generation type: {$type}."),
	};
}

protected function PasswordHasher(string $type): callable
{
	return match ($type) {
		'pbkdf2' => fn($password, $salt, $iterations, $keyLength, $algo = 'sha256') =>
			hash_pbkdf2(
				$algo,
				$password,
				$salt,
				$iterations ?? ($this->config['pwHash']['iterations'] ?? 10000),
				$keyLength ?? ($this->config['pwHash']['keyLength'] ?? 32),
				true // Raw output
			),

		'bcrypt' => fn($password) =>
			password_hash(
				$password,
				PASSWORD_BCRYPT,
				['cost' => $this->config['pwHash']['bcryptCost'] ?? 10]
			),

		default => throw new CryptoException("Unsupported password hashing type: {$type}."),
	};
}

protected function KeyDeriver(string $type): callable
{
	return match ($type) {
		'pbkdf2' => fn($password, $salt, $iterations, $keyLength, $algo = 'sha256') =>
			hash_pbkdf2(
				$algo,
				$password,
				$salt,
				$iterations ?? ($this->config['pwHash']['iterations'] ?? 10000),
				$keyLength ?? ($this->config['pwHash']['keyLength'] ?? 32),
				true // Raw output
			),

		'hkdf' => fn($keyMaterial, $salt, $info, $length, $algo = 'sha256') =>
			hash_hkdf(
				$algo,
				$keyMaterial,
				$length ?? ($this->config['pwHash']['keyLength'] ?? 32),
				$info,
				$salt
			),

		default => throw new CryptoException("Unsupported key derivation type: {$type}."),
	};
}

protected function Encryptor(string $type): callable
{
	return match ($type) {
		// Symmetric Key Encryption
		'aes128cbc' => fn($message, $iv, $key) =>
			openssl_encrypt(
				$message,
				'aes-128-cbc',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv
			),

		'aes256cbc' => fn($message, $iv, $key) =>
			openssl_encrypt(
				$message,
				'aes-256-cbc',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv
			),

		'aes128gcm' => fn($message, $iv, $key, &$tag, $aad = '') =>
			openssl_encrypt(
				$message,
				'aes-128-gcm',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$aad
			),

		'aes256gcm' => fn($message, $iv, $key, &$tag, $aad = '') =>
			openssl_encrypt(
				$message,
				'aes-256-gcm',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$aad
			),

		// Public Key Encryption
		'rsa' => fn($message, $publicKey, $padding = 'pkcs1') =>
			$this->config['padding'][$padding] ??
			fn() => openssl_public_encrypt(
				$message,
				$encryptedData,
				$publicKey,
				$this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING
			) ? $encryptedData : throw new CryptoException("RSA encryption failed."),

		// AEAD Encryption
		'aeadAes128' => fn($message, $iv, $key, &$tag, $aad = '') =>
			openssl_encrypt(
				$message,
				'aes-128-gcm',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$aad
			),

		default => throw new CryptoException("Unsupported encryption type: {$type}."),
	};
}

protected function Decryptor(string $type): callable
{
	return match ($type) {
		// Symmetric Key Decryption
		'aes128cbc' => fn($ciphertext, $iv, $key) =>
			openssl_decrypt(
				$ciphertext,
				'aes-128-cbc',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv
			) ?: throw new CryptoException("AES-128-CBC decryption failed."),

		'aes256cbc' => fn($ciphertext, $iv, $key) =>
			openssl_decrypt(
				$ciphertext,
				'aes-256-cbc',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv
			) ?: throw new CryptoException("AES-256-CBC decryption failed."),

		'aes128gcm' => fn($ciphertext, $iv, $key, $tag, $aad = '') =>
			openssl_decrypt(
				$ciphertext,
				'aes-128-gcm',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$aad
			) ?: throw new CryptoException("AES-128-GCM decryption failed."),

		'aes256gcm' => fn($ciphertext, $iv, $key, $tag, $aad = '') =>
			openssl_decrypt(
				$ciphertext,
				'aes-256-gcm',
				$key,
				$this->config['options']['rawData'] ?? OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$aad
			) ?: throw new CryptoException("AES-256-GCM decryption failed."),

		// Public Key Decryption
		'rsa' => fn($ciphertext, $privateKey, $padding = 'pkcs1') =>
			$this->config['padding'][$padding] ??
			fn() => openssl_private_decrypt(
				$ciphertext,
				$decryptedData,
				$privateKey,
				$this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING
			) ? $decryptedData : throw new CryptoException("RSA decryption failed."),

		default => throw new CryptoException("Unsupported decryption type: {$type}."),
	};
}

protected function Decryptor(string $type): callable
{
	return match ($type) {
		// Secret Key Decryption
		'secretKey' => fn($encryptedMessage, $key, $iv, $cipher = 'aes256cbc', $options = OPENSSL_RAW_DATA) =>
			openssl_decrypt($encryptedMessage, $this->config['ciphers'][$cipher] ?? 'aes-256-cbc', $key, $options, $iv),

		// Public Key Decryption
		'publicKey' => fn($encryptedMessage, $privateKey, $padding = 'pkcs1') =>
			openssl_private_decrypt(
				$encryptedMessage,
				$decryptedData,
				$privateKey,
				$this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING
			) ? $decryptedData : throw new CryptoException("Public key decryption failed."),

		// AEAD Decryption
		'aead' => fn($encryptedMessage, $key, $iv, $aad, $tag, $cipher = 'aes256gcm', $options = OPENSSL_RAW_DATA) =>
			openssl_decrypt(
				$encryptedMessage,
				$this->config['ciphers'][$cipher] ?? 'aes-256-gcm',
				$key,
				$options,
				$iv,
				$tag,
				$aad
			),

		// CMS Decryption
		'cms' => fn($encryptedMessage, $cert, $key, $flags = []) =>
			openssl_cms_decrypt(
				$encryptedMessage,
				$cert,
				$key,
				array_reduce($flags, fn($carry, $flag) => $carry | ($this->config['cms'][$flag] ?? 0), 0)
			),

		// PKCS7 Decryption
		'pkcs7' => fn($encryptedMessage, $certPath, $keyPath, $flags = []) =>
			openssl_pkcs7_decrypt(
				$this->tempFile($encryptedMessage),
				$this->tempFile(),
				$certPath,
				$keyPath,
				array_reduce($flags, fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? 0), 0)
			),

		default => throw new CryptoException("Unsupported decryption type: {$type}."),
	};
}

protected function Encryptor(string $type): callable
{
	return match ($type) {
		// Secret Key Encryption
		'secretKey' => fn($message, $key, $iv, $cipher = 'aes256cbc', $options = OPENSSL_RAW_DATA) =>
			openssl_encrypt($message, $this->config['ciphers'][$cipher] ?? 'aes-256-cbc', $key, $options, $iv),

		// Public Key Encryption
		'publicKey' => fn($message, $publicKey, $padding = 'pkcs1') =>
			openssl_public_encrypt(
				$message,
				$encryptedData,
				$publicKey,
				$this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING
			) ? $encryptedData : throw new CryptoException("Public key encryption failed."),

		// AEAD Encryption
		'aead' => fn($message, $key, $iv, $aad, &$tag, $cipher = 'aes256gcm', $options = OPENSSL_RAW_DATA) =>
			openssl_encrypt(
				$message,
				$this->config['ciphers'][$cipher] ?? 'aes-256-gcm',
				$key,
				$options,
				$iv,
				$tag,
				$aad
			),

		// CMS Encryption
		'cms' => fn($message, $certs, $cipher = 'aes256cbc', $flags = []) =>
			openssl_cms_encrypt(
				$message,
				'php://memory',
				$certs,
				[$this->config['ciphers'][$cipher] ?? 'aes-256-cbc'],
				array_reduce($flags, fn($carry, $flag) => $carry | ($this->config['cms'][$flag] ?? 0), 0)
			),

		// PKCS7 Encryption
		'pkcs7' => fn($message, $certs, $flags = []) =>
			openssl_pkcs7_encrypt(
				$this->tempFile($message),
				$this->tempFile(),
				$certs,
				[],
				array_reduce($flags, fn($carry, $flag) => $carry | ($this->config['pkcs7'][$flag] ?? 0), 0)
			),

		default => throw new CryptoException("Unsupported encryption type: {$type}."),
	};
}

public function encryptWithAEAD(string $data, string $key, string $cipher = 'aes256gcm', ?string $iv = null, string $aad = ''): string
{
	try {
		$cipherConst = $this->config['ciphers'][$cipher] ?? 'aes-256-gcm';
		$ivLength = openssl_cipher_iv_length($cipherConst);

		if ($iv === null) {
			$iv = random_bytes($ivLength);
		}

		$tag = '';
		$encrypted = openssl_encrypt($data, $cipherConst, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);

		if ($encrypted === false) {
			throw new CryptoException("AEAD encryption failed.");
		}

		return $iv . $tag . $encrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("AEAD encryption failed: " . $e->getMessage());
	}
}

public function decryptWithAEAD(string $data, string $key, string $cipher = 'aes256gcm', string $aad = ''): ?string
{
	try {
		$cipherConst = $this->config['ciphers'][$cipher] ?? 'aes-256-gcm';
		$ivLength = openssl_cipher_iv_length($cipherConst);
		$tagLength = 16; // Default tag length for GCM

		$iv = substr($data, 0, $ivLength);
		$tag = substr($data, $ivLength, $tagLength);
		$encryptedData = substr($data, $ivLength + $tagLength);

		$decrypted = openssl_decrypt($encryptedData, $cipherConst, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);

		if ($decrypted === false) {
			throw new CryptoException("Decryption failed.");
		}

		return $decrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("AEAD decryption failed: " . $e->getMessage());
	}
}

public function encryptWithPublicKey(string $data, $publicKey, string $padding = 'pkcs1'): string
{
	try {
		$paddingConst = $this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING;
		if (!openssl_public_encrypt($data, $encrypted, $publicKey, $paddingConst)) {
			throw new CryptoException("Public key encryption failed.");
		}
		return $encrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("Public key encryption failed: " . $e->getMessage());
	}
}

public function decryptWithPrivateKey(string $data, $privateKey, string $padding = 'pkcs1'): ?string
{
	try {
		$paddingConst = $this->config['padding'][$padding] ?? OPENSSL_PKCS1_PADDING;
		if (!openssl_private_decrypt($data, $decrypted, $privateKey, $paddingConst)) {
			throw new CryptoException("Private key decryption failed.");
		}
		return $decrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("Private key decryption failed: " . $e->getMessage());
	}
}

public function encryptWithSecretKey(string $data, string $key, string $cipher = 'aes256cbc', ?string $iv = null): string
{
	try {
		$cipherConst = $this->config['ciphers'][$cipher] ?? 'aes-256-cbc';
		$ivLength = openssl_cipher_iv_length($cipherConst);

		if ($iv === null) {
			$iv = random_bytes($ivLength);
		}

		$encrypted = openssl_encrypt($data, $cipherConst, $key, OPENSSL_RAW_DATA, $iv);

		if ($encrypted === false) {
			throw new CryptoException("Encryption failed.");
		}

		return $iv . $encrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("Secret key encryption failed: " . $e->getMessage());
	}
}

public function decryptWithSecretKey(string $data, string $key, string $cipher = 'aes256cbc'): ?string
{
	try {
		$cipherConst = $this->config['ciphers'][$cipher] ?? 'aes-256-cbc';
		$ivLength = openssl_cipher_iv_length($cipherConst);

		$iv = substr($data, 0, $ivLength);
		$encryptedData = substr($data, $ivLength);

		$decrypted = openssl_decrypt($encryptedData, $cipherConst, $key, OPENSSL_RAW_DATA, $iv);

		if ($decrypted === false) {
			throw new CryptoException("Decryption failed.");
		}

		return $decrypted;
	} catch (\Throwable $e) {
		throw new CryptoException("Secret key decryption failed: " . $e->getMessage());
	}
}
<
