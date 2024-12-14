<?php

namespace App\Drivers\Crypto;

use App\Abstracts\Data\Crypto;

class SodiumCrypto extends Crypto
{
	protected readonly array $config;

	public function __construct()
	{
		$this->config = [
			'version' =>
				'libraryVersion' => SODIUM_LIBRARY_VERSION,
				'libraryMajorVersion' => SODIUM_LIBRARY_MAJOR_VERSION,
				'libraryMinorVersion' => SODIUM_LIBRARY_MINOR_VERSION,
			],
			'base64Variants' => [
				'original' => SODIUM_BASE64_VARIANT_ORIGINAL,
				'originalNoPadding' => SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING,
				'urlsafe' => SODIUM_BASE64_VARIANT_URLSAFE,
				'urlsafeNoPadding' => SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
			],
			'aeadAegis128l' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_AEGIS128L_KEYBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_AEGIS128L_NSECBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_AEGIS128L_NPUBBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_AEGIS128L_ABYTES,
			],
			'aeadAegis256' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_AEGIS256_KEYBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_AEGIS256_NSECBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_AEGIS256_NPUBBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_AEGIS256_ABYTES,
			],
			'aeadAes256gcm' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_AES256GCM_NSECBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_AES256GCM_ABYTES,
			],
			'aeadChacha20poly1305' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NSECBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_ABYTES,
			],
			'aeadChacha20poly1305Ietf' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NSECBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_ABYTES,
			],
			'aeadXchacha20poly1305Ietf' => [
				'keyBytes' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
				'npubBytes' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES,
				'nsecBytes' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NSECBYTES,
				'aBytes' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES,
			],
			'auth' => [
				'bytes' => SODIUM_CRYPTO_AUTH_BYTES,
				'keyBytes' => SODIUM_CRYPTO_AUTH_KEYBYTES,
			],
			'box' => [
				'sealBytes' => SODIUM_CRYPTO_BOX_SEALBYTES,
				'secretKeyBytes' => SODIUM_CRYPTO_BOX_SECRETKEYBYTES,
				'publicKeyBytes' => SODIUM_CRYPTO_BOX_PUBLICKEYBYTES,
				'keyPairBytes' => SODIUM_CRYPTO_BOX_KEYPAIRBYTES,
				'macBytes' => SODIUM_CRYPTO_BOX_MACBYTES,
				'nonceBytes' => SODIUM_CRYPTO_BOX_NONCEBYTES,
				'seedBytes' => SODIUM_CRYPTO_BOX_SEEDBYTES,
			],
			'kdf' => [
				'bytesMin' => SODIUM_CRYPTO_KDF_BYTES_MIN,
				'bytesMax' => SODIUM_CRYPTO_KDF_BYTES_MAX,
				'contextBytes' => SODIUM_CRYPTO_KDF_CONTEXTBYTES,
				'keyBytes' => SODIUM_CRYPTO_KDF_KEYBYTES,
			],
			'kx' => [
				'seedBytes' => SODIUM_CRYPTO_KX_SEEDBYTES,
				'sessionKeyBytes' => SODIUM_CRYPTO_KX_SESSIONKEYBYTES,
				'publicKeyBytes' => SODIUM_CRYPTO_KX_PUBLICKEYBYTES,
				'secretKeyBytes' => SODIUM_CRYPTO_KX_SECRETKEYBYTES,
				'keyPairBytes' => SODIUM_CRYPTO_KX_KEYPAIRBYTES,
			],
			'genericHash' => [
				'bytes' => SODIUM_CRYPTO_GENERICHASH_BYTES,
				'bytesMin' => SODIUM_CRYPTO_GENERICHASH_BYTES_MIN,
				'bytesMax' => SODIUM_CRYPTO_GENERICHASH_BYTES_MAX,
				'keyBytes' => SODIUM_CRYPTO_GENERICHASH_KEYBYTES,
				'keyBytesMin' => SODIUM_CRYPTO_GENERICHASH_KEYBYTES_MIN,
				'keyBytesMax' => SODIUM_CRYPTO_GENERICHASH_KEYBYTES_MAX,
			],
			'pwHash' => [
				'algArgon2i13' => SODIUM_CRYPTO_PWHASH_ALG_ARGON2I13,
				'algArgon2id13' => SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
				'algDefault' => SODIUM_CRYPTO_PWHASH_ALG_DEFAULT,
				'saltBytes' => SODIUM_CRYPTO_PWHASH_SALTBYTES,
				'strPrefix' => SODIUM_CRYPTO_PWHASH_STRPREFIX,
				'opslimitInteractive' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
				'memlimitInteractive' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
				'opslimitModerate' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
				'memlimitModerate' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE,
				'opslimitSensitive' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
				'memlimitSensitive' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE,
			],
			'pwHashScryptsalsa208sha256' => [
				'saltBytes' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES,
				'strPrefix' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_STRPREFIX,
				'opslimitInteractive' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
				'memlimitInteractive' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE,
				'opslimitSensitive' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_SENSITIVE,
				'memlimitSensitive' => SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_SENSITIVE,
			],
			'ristretto255' => [
				'bytes' => SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES,
				'hashBytes' => SODIUM_CRYPTO_CORE_RISTRETTO255_HASHBYTES,
				'nonReducedScalarBytes' => SODIUM_CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES,
				'scalarBytes' => SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES,
			],
			'scalarMult' => [
				'bytes' => SODIUM_CRYPTO_SCALARMULT_BYTES,
				'scalarBytes' => SODIUM_CRYPTO_SCALARMULT_SCALARBYTES,
				'ristretto255Bytes' => SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_BYTES,
				'ristretto255ScalarBytes' => SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES,
			],
			'shortHash' => [
				'bytes' => SODIUM_CRYPTO_SHORTHASH_BYTES,
				'keyBytes' => SODIUM_CRYPTO_SHORTHASH_KEYBYTES,
			],
			'secretBox' => [
				'keyBytes' => SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
				'macBytes' => SODIUM_CRYPTO_SECRETBOX_MACBYTES,
				'nonceBytes' => SODIUM_CRYPTO_SECRETBOX_NONCEBYTES,
			],
			'secretStreamXchacha20poly1305' => [
				'aBytes' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES,
				'headerBytes' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES,
				'keyBytes' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES,
				'messageBytesMax' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_MESSAGEBYTES_MAX,
				'tagFinal' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL,
				'tagMessage' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE,
				'tagPush' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_PUSH,
				'tagRekey' => SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_REKEY,
			],
			'sign' => [
				'bytes' => SODIUM_CRYPTO_SIGN_BYTES,
				'seedBytes' => SODIUM_CRYPTO_SIGN_SEEDBYTES,
				'publicKeyBytes' => SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
				'secretKeyBytes' => SODIUM_CRYPTO_SIGN_SECRETKEYBYTES,
				'keyPairBytes' => SODIUM_CRYPTO_SIGN_KEYPAIRBYTES,
			],
			'stream' => [
				'nonceBytes' => SODIUM_CRYPTO_STREAM_NONCEBYTES,
				'keyBytes' => SODIUM_CRYPTO_STREAM_KEYBYTES,
				'xchacha20KeyBytes' => SODIUM_CRYPTO_STREAM_XCHACHA20_KEYBYTES,
				'xchacha20NonceBytes' => SODIUM_CRYPTO_STREAM_XCHACHA20_NONCEBYTES,
			],
		];
	}

	protected function DataConverter(string $type): callable
	{
		return match ($type) {
			'bin2base64' => fn(string $data, string $variant = 'urlsafe') =>
				sodium_bin2base64(
					$data,
					$this->config['base64Variants'][$variant]
					?? $this->config['base64Variants']['urlsafe']
				),

			'base642bin' => fn(string $data, string $variant = 'urlsafe') =>
				sodium_base642bin(
					$data,
					$this->config['base64Variants'][$variant]
					?? $this->config['base64Variants']['urlsafe']
				),

			'bin2hex' => fn(string $data) =>
				sodium_bin2hex($data),

			'hex2bin' => fn(string $data) =>
				sodium_hex2bin($data),

			default => throw new CryptoException("Unsupported data conversion type: {$type}."),
		};
	}

	protected function ScalarHandler(string $operation): callable
	{
		return match ($operation) {
			// Add two scalars
			'add' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_add($scalarA, $scalarB)
				?: throw new CryptoException("Scalar addition failed."),

			// Subtract two scalars
			'subtract' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_sub($scalarA, $scalarB)
				?: throw new CryptoException("Scalar subtraction failed."),

			// Multiply two scalars
			'multiply' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_mul($scalarA, $scalarB)
				?: throw new CryptoException("Scalar multiplication failed."),

			// Invert a scalar
			'invert' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_invert($scalar)
				?: throw new CryptoException("Scalar inversion failed."),

			// Negate a scalar
			'negate' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_negate($scalar)
				?: throw new CryptoException("Scalar negation failed."),

			// Reduce a scalar
			'reduce' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_reduce($scalar)
				?: throw new CryptoException("Scalar reduction failed."),

			// Perform scalar multiplication with a point
			'scalarmult' => fn(string $scalar, string $point) =>
				sodium_crypto_scalarmult($scalar, $point)
				?: throw new CryptoException("Scalar multiplication with point failed."),

			// Perform scalar multiplication with the base point
			'base' => fn(string $scalar) =>
				sodium_crypto_scalarmult_base($scalar)
				?: throw new CryptoException("Scalar multiplication with base point failed."),

			// Increment a large scalar
			'increment' => fn(string $largeNumber) =>
				sodium_increment($largeNumber),

			// Compare two scalars
			'compare' => fn(string $numberA, string $numberB) =>
				sodium_compare($numberA, $numberB),

			// Generate a random scalar
			'random' => fn() =>
				sodium_crypto_core_ristretto255_scalar_random()
				?: throw new CryptoException("Random scalar generation failed."),

			// Complement a scalar
			'complement' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_complement($scalar)
				?: throw new CryptoException("Scalar complement failed."),

			// Validate and perform scalar multiplication using Ristretto255
			'ristrettoMult' => fn(string $scalar, string $point) =>
				sodium_crypto_scalarmult_ristretto255($scalar, $point)
				?: throw new CryptoException("Ristretto255 scalar multiplication failed."),

			// Perform scalar multiplication with Ristretto255 base point
			'ristrettoBase' => fn(string $scalar) =>
				sodium_crypto_scalarmult_ristretto255_base($scalar)
				?: throw new CryptoException("Ristretto255 scalar multiplication with base point failed."),

			default => throw new CryptoException("Unsupported scalar operation: {$operation}."),
		};
	}

	protected function Hasher(string $type): callable
	{
		return match ($type) {
			'generic' => fn(
				string $data,
				?string $key = null,
				?int $length = null
			) => sodium_crypto_generichash(
				$data,
				$key,
				$length ?? $this->config['genericHash']['bytes'] ?? 32
			),

			'short' => fn(string $data, string $key) =>
				sodium_crypto_shorthash(
					$data,
					$key
				),

			'genericKeygen' => fn() =>
				sodium_crypto_generichash_keygen(),

			'shortKeygen' => fn() =>
				sodium_crypto_shorthash_keygen(),

			'stateful' => fn(
				string $action,
				$state = null,
				string $data = '',
				?string $key = null,
				?int $length = null
			) => match ($action) {
				'init' => sodium_crypto_generichash_init(
					$key,
					$length ?? $this->config['genericHash']['bytes'] ?? 32
				),
				'update' => sodium_crypto_generichash_update($state, $data),
				'final' => sodium_crypto_generichash_final($state),
				default => throw new CryptoException("Unsupported stateful hashing action: {$action}."),
			},

			default => throw new CryptoException("Unsupported hash type: {$type}."),
		};
	}

	protected function PasswordHasher(string $type): callable
	{
		return match ($type) {
			'argon2i' => fn(
				string $password,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str(
				$password,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			),

			'argon2id' => fn(
				string $password,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str(
				$password,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			),

			'scrypt' => fn(
				string $password,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_scryptsalsa208sha256(
				$password,
				$opslimit ?? $this->config['pwHashScryptsalsa208sha256']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHashScryptsalsa208sha256']['memlimitInteractive']
			),

			default => throw new CryptoException("Unsupported password hash type: {$type}."),
		};
	}

	protected function PasswordVerifier(string $action): callable
	{
		return match ($action) {
			'verify' => fn(string $hash, string $password) =>
				sodium_crypto_pwhash_str_verify($hash, $password)
				?: throw new CryptoException("Password verification failed."),

			'rehash' => fn(
				string $hash,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str_needs_rehash(
				$hash,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			),

			'scryptVerify' => fn(string $hash, string $password) =>
				sodium_crypto_pwhash_scryptsalsa208sha256_str_verify(
					$hash,
					$password
				) ?: throw new CryptoException("Scrypt password verification failed."),

			default => throw new CryptoException("Unsupported password verifier action: {$action}."),
		};
	}

	protected function RandomGenerator(string $type, ?int $length = null): callable
	{
		return match ($type) {
			'default' => fn() =>
				random_bytes(
					$length ?? $this->config['stream']['keyBytes'] ?? 32
				),

			'xchacha20' => fn() =>
				random_bytes(
					$length ?? $this->config['stream']['xchacha20KeyBytes'] ?? 32
				),

			'short' => fn() =>
				random_bytes(
					$length ?? $this->config['shortHash']['bytes'] ?? 16
				),

			'long' => fn() =>
				random_bytes(
					$length ?? $this->config['genericHash']['bytesMax'] ?? 128
				),

			'passwordSalt' => fn() =>
				random_bytes(
					$length ?? $this->config['pwHash']['saltBytes'] ?? 32
				),

			'scalar' => fn() =>
				random_bytes(
					$length ?? $this->config['scalarMult']['scalarBytes'] ?? 32
				),

			'ristretto' => fn() =>
				random_bytes(
					$length ?? $this->config['ristretto255']['scalarBytes'] ?? 32
				),

			'custom' => fn() =>
				random_bytes(
					$length ?? throw new CryptoException("Custom random generator requires a length parameter.")
				),

			default => throw new CryptoException("Unsupported random generator type: {$type}."),
		};
	}

	protected function RistrettoHandler(string $operation): callable
	{
		return match ($operation) {
			'add' => fn(string $pointA, string $pointB) =>
				sodium_crypto_core_ristretto255_add($pointA, $pointB)
				?: throw new CryptoException("Ristretto255 addition failed."),

			'subtract' => fn(string $pointA, string $pointB) =>
				sodium_crypto_core_ristretto255_sub($pointA, $pointB)
				?: throw new CryptoException("Ristretto255 subtraction failed."),

			'hash' => fn(string $data) =>
				sodium_crypto_core_ristretto255_from_hash($data)
				?: throw new CryptoException("Ristretto255 hashing failed."),

			'validate' => fn(string $point) =>
				sodium_crypto_core_ristretto255_is_valid_point($point),

			'random' => fn() =>
				sodium_crypto_core_ristretto255_random()
				?: throw new CryptoException("Failed to generate random Ristretto255 point."),

			default => throw new CryptoException("Unsupported Ristretto255 operation: {$operation}."),
		};
	}

	protected function KeyExchanger(string $type): callable
	{
		return match ($type) {
			'client' => fn(string $clientPk, string $clientSk, string $serverPk) =>
				sodium_crypto_kx_client_session_keys($clientPk, $clientSk, $serverPk)
				?: throw new CryptoException("Failed to generate client session keys."),

			'server' => fn(string $serverPk, string $serverSk, string $clientPk) =>
				sodium_crypto_kx_server_session_keys($serverPk, $serverSk, $clientPk)
				?: throw new CryptoException("Failed to generate server session keys."),

			default => throw new CryptoException("Unsupported key exchange type: {$type}."),
		};
	}

	protected function KeyExtractor(string $type): callable
	{
		return match ($type) {
			'publicKey' => fn(string $keyPair) =>
				sodium_crypto_sign_publickey($keyPair)
				?: throw new CryptoException("Failed to extract public key."),

			'secretKey' => fn(string $keyPair) =>
				sodium_crypto_sign_secretkey($keyPair)
				?: throw new CryptoException("Failed to extract secret key."),

			'publicKeyFromSecret' => fn(string $secretKey) =>
				sodium_crypto_sign_publickey_from_secretkey($secretKey)
				?: throw new CryptoException("Failed to derive public key from secret key."),

			'ed25519ToCurve25519Public' => fn(string $publicKey) =>
				sodium_crypto_sign_ed25519_pk_to_curve25519($publicKey)
				?: throw new CryptoException("Failed to convert Ed25519 public key to Curve25519."),

			'ed25519ToCurve25519Secret' => fn(string $secretKey) =>
				sodium_crypto_sign_ed25519_sk_to_curve25519($secretKey)
				?: throw new CryptoException("Failed to convert Ed25519 secret key to Curve25519."),

			default => throw new CryptoException("Unsupported key extraction type: {$type}."),
		};
	}

	protected function KeyGenerator(string $type): callable
	{
		return match ($type) {
			'secretBox' => fn() =>
				sodium_crypto_secretbox_keygen(),

			'auth' => fn() =>
				sodium_crypto_auth_keygen(),

			'genericHash' => fn() =>
				sodium_crypto_generichash_keygen(),

			'shortHash' => fn() =>
				sodium_crypto_shorthash_keygen(),

			'keyExchange' => fn() =>
				sodium_crypto_kx_keypair(),

			'sign' => fn() =>
				sodium_crypto_sign_keypair(),

			'aes256gcm' => fn() =>
				sodium_crypto_aead_aes256gcm_keygen(),

			'chacha20poly1305' => fn() =>
				sodium_crypto_aead_chacha20poly1305_keygen(),

			'chacha20poly1305Ietf' => fn() =>
				sodium_crypto_aead_chacha20poly1305_ietf_keygen(),

			'xchacha20poly1305' => fn() =>
				sodium_crypto_aead_xchacha20poly1305_ietf_keygen(),

			'aegis128l' => fn() =>
				sodium_crypto_aead_aegis128l_keygen(),

			'aegis256' => fn() =>
				sodium_crypto_aead_aegis256_keygen(),

			'stream' => fn() =>
				sodium_crypto_stream_keygen(),

			'xchacha20' => fn() =>
				sodium_crypto_stream_xchacha20_keygen(),

			'secretStream' => fn() =>
				sodium_crypto_secretstream_xchacha20poly1305_keygen(),

			'kdf' => fn() =>
				sodium_crypto_kdf_keygen(),

			default => throw new CryptoException("Unsupported key generation type: {$type}."),
		};
	}

	protected function Hasher(string $type): callable
	{
		return match ($type) {
			'generic' => fn(
				string $data,
				?string $key = null,
				?int $length = null
			) => sodium_crypto_generichash(
				$data,
				$key,
				$length ?? $this->config['genericHash']['bytes'] ?? 32
			),

			'short' => fn(
				string $data,
				string $key
			) => sodium_crypto_shorthash(
				$data,
				$key
			),

			'stateful' => fn(
				string $action,
				$state = null,
				string $data = '',
				?string $key = null,
				?int $length = null
			) => match ($action) {
				'init' => sodium_crypto_generichash_init(
					$key,
					$length ?? $this->config['genericHash']['bytes'] ?? 32
				),
				'update' => sodium_crypto_generichash_update($state, $data),
				'final' => sodium_crypto_generichash_final($state),
				default => throw new CryptoException("Unsupported stateful hashing action: {$action}."),
			},

			default => throw new CryptoException("Unsupported hash type: {$type}."),
		};
	}

	protected function ScalarHandler(string $operation): callable
	{
		return match ($operation) {
			'add' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_add($scalarA, $scalarB)
				?: throw new CryptoException("Scalar addition failed."),

			'subtract' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_sub($scalarA, $scalarB)
				?: throw new CryptoException("Scalar subtraction failed."),

			'multiply' => fn(string $scalarA, string $scalarB) =>
				sodium_crypto_core_ristretto255_scalar_mul($scalarA, $scalarB)
				?: throw new CryptoException("Scalar multiplication failed."),

			'invert' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_invert($scalar)
				?: throw new CryptoException("Scalar inversion failed."),

			'negate' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_negate($scalar)
				?: throw new CryptoException("Scalar negation failed."),

			'reduce' => fn(string $scalar) =>
				sodium_crypto_core_ristretto255_scalar_reduce($scalar)
				?: throw new CryptoException("Scalar reduction failed."),

			'scalarmult' => fn(string $scalar, string $point) =>
				sodium_crypto_scalarmult($scalar, $point)
				?: throw new CryptoException("Scalar multiplication with point failed."),

			'base' => fn(string $scalar) =>
				sodium_crypto_scalarmult_base($scalar)
				?: throw new CryptoException("Scalar multiplication with base point failed."),

			default => throw new CryptoException("Unsupported scalar operation: {$operation}."),
		};
	}

	protected function MemoryHandler(string $action): callable
	{
		return match ($action) {
			'clear' => fn(string &$data) =>
				sodium_memzero($data),

			'compare' => fn(string $a, string $b) =>
				sodium_memcmp($a, $b) === 0,

			'increment' => fn(string &$data) =>
				sodium_increment($data),

			'add' => fn(string &$dataA, string &$dataB) =>
				sodium_add($dataA, $dataB)
				?: throw new CryptoException("Failed to add large numbers."),

			'pad' => fn(string $data, int $blockSize) =>
				sodium_pad($data, $blockSize),

			'unpad' => fn(string $data, int $blockSize) =>
				sodium_unpad($data, $blockSize),

			default => throw new CryptoException("Unsupported memory action: {$action}."),
		};
	}

	protected function RistrettoHandler(string $operation): callable
	{
		return match ($operation) {
			'add' => fn(string $pointA, string $pointB) =>
				sodium_crypto_core_ristretto255_add($pointA, $pointB)
				?: throw new CryptoException("Ristretto255 addition failed."),

			'subtract' => fn(string $pointA, string $pointB) =>
				sodium_crypto_core_ristretto255_sub($pointA, $pointB)
				?: throw new CryptoException("Ristretto255 subtraction failed."),

			'hash' => fn(string $data) =>
				sodium_crypto_core_ristretto255_from_hash($data)
				?: throw new CryptoException("Ristretto255 hashing failed."),

			'validate' => fn(string $point) =>
				sodium_crypto_core_ristretto255_is_valid_point($point),

			'random' => fn() =>
				sodium_crypto_core_ristretto255_random()
				?: throw new CryptoException("Failed to generate random Ristretto255 point."),

			default => throw new CryptoException("Unsupported Ristretto255 operation: {$operation}."),
		};
	}

	protected function PasswordHasher(string $type): callable
	{
		return match ($type) {
			'argon2i' => fn(
				string $password,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str(
				$password,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			) ?: throw new CryptoException("Password hashing with Argon2i failed."),

			'argon2id' => fn(
				string $password,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str(
				$password,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			) ?: throw new CryptoException("Password hashing with Argon2id failed."),

			default => throw new CryptoException("Unsupported password hash type: {$type}."),
		};
	}

	protected function PasswordVerifier(string $action): callable
	{
		return match ($action) {
			'verify' => fn(string $hash, string $password) =>
				sodium_crypto_pwhash_str_verify(
					$hash,
					$password
				) ?: throw new CryptoException("Password verification failed."),

			'rehash' => fn(
				string $hash,
				?int $opslimit = null,
				?int $memlimit = null
			) => sodium_crypto_pwhash_str_needs_rehash(
				$hash,
				$opslimit ?? $this->config['pwHash']['opslimitInteractive'],
				$memlimit ?? $this->config['pwHash']['memlimitInteractive']
			) ?: throw new CryptoException("Password rehash check failed."),

			default => throw new CryptoException("Unsupported password verifier action: {$action}."),
		};
	}

	protected function RandomGenerator(string $type, ?int $length = null): callable
	{
		return match ($type) {
			'default' => fn() =>
				random_bytes(
					$length ?? $this->config['stream']['keyBytes'] ?? 32
				),

			'xchacha20' => fn() =>
				random_bytes(
					$length ?? $this->config['stream']['xchacha20KeyBytes'] ?? 32
				),

			'short' => fn() =>
				random_bytes(
					$length ?? $this->config['shortHash']['bytes'] ?? 16
				),

			'long' => fn() =>
				random_bytes(
					$length ?? $this->config['genericHash']['bytesMax'] ?? 128
				),

			'passwordSalt' => fn() =>
				random_bytes(
					$length ?? $this->config['pwHash']['saltBytes'] ?? 32
				),

			'scalar' => fn() =>
				random_bytes(
					$length ?? $this->config['scalarMult']['scalarBytes'] ?? 32
				),

			'ristretto' => fn() =>
				random_bytes(
					$length ?? $this->config['ristretto255']['scalarBytes'] ?? 32
				),

			'custom' => fn() =>
				random_bytes(
					$length ?? throw new CryptoException("Custom random generator requires a length parameter.")
				),

			default => throw new CryptoException("Unsupported random generator type: {$type}."),
		};
	}

	protected function Decryptor(string $type): callable
	{
		return match ($type) {
			// Secret Key Decryption
			'secretBox' => fn(string $ciphertext, string $nonce, string $key) =>
				sodium_crypto_secretbox_open($ciphertext, $nonce, $key)
				?: throw new CryptoException("Failed to decrypt with SecretBox."),

			// Public Key Decryption (Box)
			'box' => fn(string $ciphertext, string $nonce, string $keypair) =>
				sodium_crypto_box_open($ciphertext, $nonce, $keypair)
				?: throw new CryptoException("Failed to decrypt with Box."),

			// Public Key Sealing Open
			'seal' => fn(string $ciphertext, string $keypair) =>
				sodium_crypto_box_seal_open($ciphertext, $keypair)
				?: throw new CryptoException("Failed to open sealed box."),

			// AEAD Decryption
			'aead' => fn(
				string $ciphertext,
				string $aad,
				string $nonce,
				string $key,
				string $cipher = 'aes256gcm'
			) => match ($cipher) {
				'aes256gcm' => sodium_crypto_aead_aes256gcm_decrypt(
					$ciphertext,
					$aad,
					$nonce,
					$key
				),
				'chacha20poly1305' => sodium_crypto_aead_chacha20poly1305_decrypt(
					$ciphertext,
					$aad,
					$nonce,
					$key
				),
				'xchacha20poly1305' => sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
					$ciphertext,
					$aad,
					$nonce,
					$key
				),
				'aegis128l' => sodium_crypto_aead_aegis128l_decrypt(
					$ciphertext,
					$aad,
					$nonce,
					$key
				),
				'aegis256' => sodium_crypto_aead_aegis256_decrypt(
					$ciphertext,
					$aad,
					$nonce,
					$key
				),
				default => throw new CryptoException("Unsupported AEAD cipher type: {$cipher}."),
			},

			// Stream Decryption
			'streamXor' => fn(string $ciphertext, string $nonce, string $key) =>
				sodium_crypto_stream_xor($ciphertext, $nonce, $key)
				?: throw new CryptoException("Failed to decrypt stream data."),

			'xchacha20StreamXor' => fn(string $ciphertext, string $nonce, string $key) =>
				sodium_crypto_stream_xchacha20_xor($ciphertext, $nonce, $key)
				?: throw new CryptoException("Failed to decrypt XChaCha20 stream data."),

			'xchacha20StreamXorIc' => fn(string $ciphertext, string $nonce, string $key, int $counter) =>
				sodium_crypto_stream_xchacha20_xor_ic($ciphertext, $nonce, $key, $counter)
				?: throw new CryptoException("Failed to decrypt XChaCha20 stream data with counter."),

			default => throw new CryptoException("Unsupported decryption type: {$type}."),
		};
	}

	protected function Encryptor(string $type): callable
	{
		return match ($type) {
			// Secret Key Encryption
			'secretBox' => fn(string $message, string $nonce, string $key) =>
				sodium_crypto_secretbox($message, $nonce, $key),

			// Public Key Encryption (Box)
			'box' => fn(string $message, string $nonce, string $keypair) =>
				sodium_crypto_box($message, $nonce, $keypair),

			// Public Key Sealing
			'seal' => fn(string $message, string $publicKey) =>
				sodium_crypto_box_seal($message, $publicKey),

			// AEAD Encryption
			'aead' => fn(
				string $message,
				string $aad,
				string $nonce,
				string $key,
				string $cipher = 'aes256gcm'
			) => match ($cipher) {
				'aes256gcm' => sodium_crypto_aead_aes256gcm_encrypt(
					$message,
					$aad,
					$nonce,
					$key
				),
				'chacha20poly1305' => sodium_crypto_aead_chacha20poly1305_encrypt(
					$message,
					$aad,
					$nonce,
					$key
				),
				'xchacha20poly1305' => sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
					$message,
					$aad,
					$nonce,
					$key
				),
				'aegis128l' => sodium_crypto_aead_aegis128l_encrypt(
					$message,
					$aad,
					$nonce,
					$key
				),
				'aegis256' => sodium_crypto_aead_aegis256_encrypt(
					$message,
					$aad,
					$nonce,
					$key
				),
				default => throw new CryptoException("Unsupported AEAD cipher type: {$cipher}."),
			},

			// Stream Encryption
			'stream' => fn(int $length, string $nonce, string $key) =>
				sodium_crypto_stream($length, $nonce, $key),

			'streamXor' => fn(string $data, string $nonce, string $key) =>
				sodium_crypto_stream_xor($data, $nonce, $key),

			'xchacha20StreamXor' => fn(string $data, string $nonce, string $key) =>
				sodium_crypto_stream_xchacha20_xor($data, $nonce, $key),

			'xchacha20StreamXorIc' => fn(string $data, string $nonce, string $key, int $counter) =>
				sodium_crypto_stream_xchacha20_xor_ic($data, $nonce, $key, $counter),

			default => throw new CryptoException("Unsupported encryption type: {$type}."),
		};
	}

	// ---- Key Derivation ----
	protected function KeyDerivation(): callable
	{
		return fn(string $key, int $subKeyId, string $context) =>
			sodium_crypto_kdf_derive_from_key($subKeyId, $context, $key);
	}
}
