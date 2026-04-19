<?php

namespace App\Drivers\Cryptography;

use App\Abstracts\Data\Crypto;
use App\Contracts\Data\CryptoInterface;
use App\Exceptions\Data\CryptoException;
use App\Utilities\Traits\{
	EncodingTrait,
	HashingTrait,
	ManipulationTrait,
	TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

class OpenSSLCrypto extends Crypto implements CryptoInterface
{
	use EncodingTrait, HashingTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait;

	protected readonly array $config;
	protected readonly array $capabilities;

	public function __construct()
	{
		$const = static fn(string $name, mixed $fallback = null): mixed =>
			defined($name) ? constant($name) : $fallback;

		$this->config = [
			'ciphers' => [
				'aes128cbc' => 'aes-128-cbc',
				'aes192cbc' => 'aes-192-cbc',
				'aes256cbc' => 'aes-256-cbc',
				'aes128gcm' => 'aes-128-gcm',
				'aes256gcm' => 'aes-256-gcm',
				'rc2_40' => 'rc2-40-cbc',
				'rc2_128' => 'rc2-cbc',
				'rc2_64' => 'rc2-64-cbc',
				'des' => 'des-cbc',
				'3des' => 'des-ede3-cbc',
			],
			'algorithms' => [
				'sha1' => 'sha1',
				'md5' => 'md5',
				'sha256' => 'sha256',
				'sha512' => 'sha512',
				'sha224' => 'sha224',
				'sha384' => 'sha384',
				'sha3_256' => 'sha3-256',
				'sha3_512' => 'sha3-512',
				'sha3_224' => 'sha3-224',
				'sha3_384' => 'sha3-384',
				'sm3' => 'sm3',
				'rmd160' => 'ripemd160',
				'md4' => 'md4',
				'dss1' => 'dss1',
				'md2' => 'md2',
				'blake2b512' => 'blake2b512',
				'blake2s256' => 'blake2s256',
			],
			'defaultAlgo' => 'sha256',
			'random' => [
				'defaultLength' => 32,
				'defaultPseudoRandomLength' => 32,
			],
			'keys' => [
				'defaultKey' => null,
				'defaultLength' => 32,
				'defaultBits' => 2048,
				'defaultCurve' => 'prime256v1',
			],
			'pwHash' => [
				'defaultAlgo' => 'sha256',
				'iterations' => 100000,
				'keyLength' => 32,
				'saltBytes' => 16,
			],
			'keyTypes' => [
				'rsa' => $const('OPENSSL_KEYTYPE_RSA'),
				'dsa' => $const('OPENSSL_KEYTYPE_DSA'),
				'dh' => $const('OPENSSL_KEYTYPE_DH'),
				'ec' => $const('OPENSSL_KEYTYPE_EC'),
			],
			'padding' => [
				'noPadding' => $const('OPENSSL_NO_PADDING', 0),
				'pkcs1' => $const('OPENSSL_PKCS1_PADDING', 1),
				'sslv23' => $const('OPENSSL_SSLV23_PADDING', 2),
				'pkcs1Oaep' => $const('OPENSSL_PKCS1_OAEP_PADDING', 4),
			],
			'pkcs7' => [
				'detached' => $const('PKCS7_DETACHED', 0),
				'text' => $const('PKCS7_TEXT', 0),
				'noIntern' => $const('PKCS7_NOINTERN', 0),
				'noVerify' => $const('PKCS7_NOVERIFY', 0),
				'noChain' => $const('PKCS7_NOCHAIN', 0),
				'noSigs' => $const('PKCS7_NOSIGS', 0),
				'noAttr' => $const('PKCS7_NOATTR', 0),
				'binary' => $const('PKCS7_BINARY', 0),
				'noCerts' => $const('PKCS7_NOCERTS', 0),
				'noCrl' => $const('PKCS7_NOCRL', 0),
				'encrypt' => $const('PKCS7_ENCRYPT', 0),
				'signed' => $const('PKCS7_SIGNED', 0),
				'envelope' => $const('PKCS7_ENVELOPE', 0),
				'signedEnvelope' => $const('PKCS7_SIGNED_ENVELOPE', 0),
				'noOldMimeType' => $const('PKCS7_NOOLDMIMETYPE', 0),
				'defaultFlags' => 0,
			],
			'cms' => [
				'text' => $const('OPENSSL_CMS_TEXT', 0),
				'binary' => $const('OPENSSL_CMS_BINARY', 0),
				'noIntern' => $const('OPENSSL_CMS_NOINTERN', 0),
				'noVerify' => $const('OPENSSL_CMS_NOVERIFY', 0),
				'noCerts' => $const('OPENSSL_CMS_NOCERTS', 0),
				'noAttr' => $const('OPENSSL_CMS_NOATTR', 0),
				'detached' => $const('OPENSSL_CMS_DETACHED', 0),
				'noSigs' => $const('OPENSSL_CMS_NOSIGS', 0),
				'oldMimeType' => $const('OPENSSL_CMS_OLDMIMETYPE', 0),
				'defaultFlags' => 0,
			],
			'ssl' => [
				'tlsv1' => $const('OPENSSL_TLSV1', 0),
				'tlsv1_1' => $const('OPENSSL_TLSV1_1', 0),
				'tlsv1_2' => $const('OPENSSL_TLSV1_2', 0),
				'tlsv1_3' => $const('OPENSSL_TLSV1_3', 0),
				'sslv2Server' => $const('OPENSSL_SSLV2_SERVER_METHOD', 0),
				'sslv3Server' => $const('OPENSSL_SSLV3_SERVER_METHOD', 0),
				'tlsv1Server' => $const('OPENSSL_TLSV1_SERVER_METHOD', 0),
				'tlsv1_1Server' => $const('OPENSSL_TLSV1_1_SERVER_METHOD', 0),
				'tlsv1_2Server' => $const('OPENSSL_TLSV1_2_SERVER_METHOD', 0),
				'tlsv1_3Server' => $const('OPENSSL_TLSV1_3_SERVER_METHOD', 0),
			],
			'options' => [
				'rawData' => $const('OPENSSL_RAW_DATA', 1),
				'zeroPadding' => $const('OPENSSL_ZERO_PADDING', 2),
				'pkcs1Padding' => $const('OPENSSL_PKCS1_PADDING', 1),
				'dontZeroPadKey' => $const('OPENSSL_DONT_ZERO_PAD_KEY', 0),
				'dontVerifyPeer' => $const('OPENSSL_DONT_VERIFY_PEER', 0),
				'sslCompression' => $const('OPENSSL_SSL_COMPRESSION', 0),
				'encodingSmime' => $const('OPENSSL_ENCODING_SMIME', 0),
				'encodingDer' => $const('OPENSSL_ENCODING_DER', 0),
				'encodingPem' => $const('OPENSSL_ENCODING_PEM', 0),
				'defaultStreamCryptoMethod' => $const('STREAM_CRYPTO_METHOD_SSLv23_CLIENT', 0),
			],
			'x509' => [
				'purposeSSLClient' => $const('X509_PURPOSE_SSL_CLIENT', 0),
				'purposeSSLServer' => $const('X509_PURPOSE_SSL_SERVER', 0),
				'purposeNSClient' => $const('X509_PURPOSE_NS_SSL_CLIENT', 0),
				'purposeNSServer' => $const('X509_PURPOSE_NS_SSL_SERVER', 0),
				'purposeSMIMESign' => $const('X509_PURPOSE_SMIME_SIGN', 0),
				'purposeSMIMEEncrypt' => $const('X509_PURPOSE_SMIME_ENCRYPT', 0),
				'purposeCRLSign' => $const('X509_PURPOSE_CRL_SIGN', 0),
				'purposeAny' => $const('X509_PURPOSE_ANY', 0),
			],
			'streamCryptoMethods' => [
				'sslv3Client' => $const('STREAM_CRYPTO_METHOD_SSLv3_CLIENT', 0),
				'sslv3Server' => $const('STREAM_CRYPTO_METHOD_SSLv3_SERVER', 0),
				'tlsv1Client' => $const('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT', 0),
				'tlsv1Server' => $const('STREAM_CRYPTO_METHOD_TLSv1_0_SERVER', 0),
				'tlsv1_1Client' => $const('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT', 0),
				'tlsv1_1Server' => $const('STREAM_CRYPTO_METHOD_TLSv1_1_SERVER', 0),
				'tlsv1_2Client' => $const('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT', 0),
				'tlsv1_2Server' => $const('STREAM_CRYPTO_METHOD_TLSv1_2_SERVER', 0),
				'tlsv1_3Client' => $const('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT', 0),
				'tlsv1_3Server' => $const('STREAM_CRYPTO_METHOD_TLSv1_3_SERVER', 0),
			],
			'pkcs12' => [
				'default' => $const('PKCS12_DEFAULT', 0),
				'noCerts' => $const('PKCS12_NO_CERTS', 0),
				'includeCertChain' => $const('PKCS12_INCLUDE_CERT_CHAIN', 0),
			],
			'version' => [
				'text' => $const('OPENSSL_VERSION_TEXT', ''),
				'number' => $const('OPENSSL_VERSION_NUMBER', 0),
			],
			'sni' => [
				'tlsextServerName' => $const('OPENSSL_TLSEXT_SERVER_NAME', ''),
			]
			];

		$this->capabilities = $this->buildCapabilities();
	}

	public function driverName(): string
	{
		return 'openssl';
	}

	public function capabilities(): array
	{
		return $this->capabilities;
	}

	public function Encryptor(string $type): callable
	{
		return match ($type) {
			'symmetric' => fn(
				$data,
				string $cipher,
				?string $key = null,
				?string $iv = null,
				?int $options = null
				) => $this->rejectFalseWithRuntimeMessage(
					openssl_encrypt(
						$data,
						$this->resolveCipherMethod($cipher),
						$key ?? $this->config['keys']['defaultKey']
							?? throw new CryptoException("Encryption key is required."),
						$options ?? $this->config['options']['rawData'],
						$iv ?? $this->RandomGenerator('generateRandomIv')($cipher)
					),
					'Symmetric encryption failed.'
				),

			'asymmetric' => fn(
				string $data,
				string $key,
				?string $padding = null
			) => openssl_public_encrypt(
				$data,
				$output,
				$key,
				$this->config['padding'][$padding ?? 'pkcs1']
					?? throw new CryptoException("Invalid padding type for asymmetric encryption.")
			) ? $output : throw new CryptoException("Public key encryption failed."),

			'privateEncrypt' => fn(
				string $data,
				string $key,
				?string $padding = null
			) => openssl_private_encrypt(
				$data,
				$output,
				$key,
				$this->config['padding'][$padding ?? 'pkcs1']
					?? throw new CryptoException("Invalid padding type for private key encryption.")
			) ? $output : throw new CryptoException("Private key encryption failed."),

			default => throw new CryptoException("Unsupported encryption type: {$type}."),
		};
	}

	public function Decryptor(string $type): callable
	{
		return match ($type) {
			'symmetric' => fn(
				$data,
				string $cipher,
				?string $key = null,
				?string $iv = null,
				?int $options = null
				) => $this->rejectFalseWithRuntimeMessage(
					openssl_decrypt(
						$data,
						$this->resolveCipherMethod($cipher),
						$key ?? $this->config['keys']['defaultKey']
							?? throw new CryptoException("Decryption key is required."),
						$options ?? $this->config['options']['rawData'],
						$iv ?? throw new CryptoException("IV is required for symmetric decryption.")
					),
					'Symmetric decryption failed.'
				),

			'asymmetric' => fn(
				string $data,
				string $key,
				?string $padding = null
			) => openssl_private_decrypt(
				$data,
				$output,
				$key,
				$this->config['padding'][$padding ?? 'pkcs1']
					?? throw new CryptoException("Invalid padding type for private key decryption.")
			) ? $output : throw new CryptoException("Private key decryption failed."),

			'publicDecrypt' => fn(
				string $data,
				string $key,
				?string $padding = null
			) => openssl_public_decrypt(
				$data,
				$output,
				$key,
				$this->config['padding'][$padding ?? 'pkcs1']
					?? throw new CryptoException("Invalid padding type for public key decryption.")
			) ? $output : throw new CryptoException("Public key decryption failed."),

			default => throw new CryptoException("Unsupported decryption type: {$type}."),
		};
	}

	public function RandomGenerator(string $type, ?int $length = null): callable
	{
		return match ($type) {
			'default' => fn(?int $overrideLength = null) =>
				random_bytes($overrideLength ?? $length ?? $this->config['random']['defaultLength'] ?? 32),

			'passwordSalt' => fn(?int $overrideLength = null) =>
				random_bytes($overrideLength ?? $length ?? $this->config['pwHash']['saltBytes'] ?? 16),

			'key' => fn(?int $overrideLength = null) =>
				random_bytes($overrideLength ?? $length ?? $this->config['keys']['defaultLength'] ?? 32),

				'generateRandomIv' => fn(string $cipher) =>
					random_bytes(
						openssl_cipher_iv_length(
							$this->resolveCipherMethod($cipher)
						)
					),

			'secureRandomBytes' => fn(?int $overrideLength = null) => [
				'bytes' => $this->rejectFalseWithRuntimeMessage(
					openssl_random_pseudo_bytes(
						$overrideLength ?? $length ?? $this->config['random']['defaultPseudoRandomLength'] ?? 32,
						$isStrong
					),
					'Pseudo-random byte generation failed.'
				),
				'isStrong' => $isStrong,
			],

			default => throw new CryptoException("Unsupported random byte generation type: {$type}."),
		};
	}

	public function PasswordHasher(string $type): callable
	{
		return match (strtolower($type)) {
			'default' => fn(string $password, array $options = []) =>
				$this->passwordHash($password, PASSWORD_DEFAULT, $options)
				?: throw new CryptoException('Password hashing failed.'),

			'bcrypt' => fn(string $password, array $options = []) =>
				$this->passwordHash($password, PASSWORD_BCRYPT, $options)
				?: throw new CryptoException('Password hashing failed.'),

			'argon2i' => fn(string $password, array $options = []) =>
				$this->passwordHash($password, PASSWORD_ARGON2I, $options)
				?: throw new CryptoException('Password hashing failed.'),

			'argon2id' => fn(string $password, array $options = []) =>
				$this->passwordHash($password, PASSWORD_ARGON2ID, $options)
				?: throw new CryptoException('Password hashing failed.'),

			default => throw new CryptoException("Unsupported password hash type: {$type}."),
		};
	}

	public function PasswordVerifier(string $action): callable
	{
		return match (strtolower($action)) {
			'verify' => fn(string $hash, string $password): bool =>
				$this->verifyPassword($password, $hash),

			'rehash' => fn(string $hash, string $type = 'default', array $options = []): bool =>
				$this->passwordNeedsRehash($hash, match (strtolower($type)) {
					'default' => PASSWORD_DEFAULT,
					'bcrypt' => PASSWORD_BCRYPT,
					'argon2i' => PASSWORD_ARGON2I,
					'argon2id' => PASSWORD_ARGON2ID,
					default => PASSWORD_DEFAULT,
				}, $options),

			default => throw new CryptoException("Unsupported password verifier action: {$action}."),
		};
	}

	public function Hasher(string $type): callable
	{
		return match ($type) {
			'pbkdf2' => fn(
				string $password,
				string $salt,
				?int $iterations = null,
				?int $keyLength = null,
				?string $algo = null
			) => hash_pbkdf2(
				$this->config['algorithms'][$algo ?? $this->config['pwHash']['defaultAlgo']]
					?? throw new CryptoException("Invalid algorithm specified for PBKDF2."),
				$password,
				$salt,
				$iterations ?? $this->config['pwHash']['iterations'],
				$keyLength ?? $this->config['pwHash']['keyLength'],
				true
			),

			'hashDigest' => fn(string $data, ?string $algo = null) =>
				$this->rejectFalseWithRuntimeMessage(
					openssl_digest(
						$data,
						$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
							?? throw new CryptoException("Invalid algorithm specified for digest.")
					),
					'Digest computation failed.'
				),

			'listHashMethods' => fn() =>
				openssl_get_md_methods()
					?: throw new CryptoException("Failed to retrieve hash methods."),

			default => throw new CryptoException("Unsupported hash type: {$type}."),
		};
	}

	public function KeyHandler(string $action): callable
	{
		return match ($action) {
			'import' => fn(string $type, string $key, ?string $passphrase = null) => match ($type) {
				'private' => $this->rejectFalseWithRuntimeMessage(
					openssl_pkey_get_private($key, $passphrase),
					'Private key import failed.'
				),
				'public' => $this->rejectFalseWithRuntimeMessage(
					openssl_pkey_get_public($key),
					'Public key import failed.'
				),
				default => throw new CryptoException("Unsupported key import type: {$type}."),
			},

			'export' => fn(string $type, $key, ?string $passphrase = null) => match ($type) {
				'private' => openssl_pkey_export($key, $output, $passphrase)
					? $output
					: throw new CryptoException($this->openSslFailureMessage('Private key export failed.')),
				'public' => $this->exportPublicKey($key),
				default => throw new CryptoException("Unsupported key export type: {$type}."),
			},

			'exportToFile' => fn($key, string $filePath, ?string $passphrase = null) =>
				openssl_pkey_export_to_file($key, $filePath, $passphrase)
					?: throw new CryptoException("Key export to file failed."),

			'generate' => fn(?string $type = null, ?int $bits = null, ?string $curve = null) => match ($type ?? 'rsa') {
				'rsa' => $this->rejectFalseWithRuntimeMessage(
					openssl_pkey_new([
						'private_key_type' => $this->config['keyTypes']['rsa'],
						'private_key_bits' => $bits ?? $this->config['keys']['defaultBits'] ?? 2048,
					]),
					'RSA key generation failed.'
				),
				'ec' => $this->rejectFalseWithRuntimeMessage(
					openssl_pkey_new([
						'private_key_type' => $this->config['keyTypes']['ec'],
						'curve_name' => $curve ?? $this->config['keys']['defaultCurve'] ?? 'prime256v1',
					]),
					'EC key generation failed.'
				),
				default => throw new CryptoException("Unsupported key type: {$type}."),
			},

			'getCurveNames' => fn() =>
				openssl_get_curve_names()
					?: throw new CryptoException("Failed to retrieve curve names."),

			'free' => function ($key): bool {
				openssl_pkey_free($key);
				return true;
			},

			default => throw new CryptoException("Unsupported key handler action: {$action}."),
		};
	}

	public function KeyExchanger(string $type): callable
	{
		return $this->KeyExchangeHandler($type);
	}

	public function CertificateHandler(string $action): callable
	{
		return match ($action) {
			'parse' => fn($certificate) =>
				openssl_x509_parse($certificate)
					?: throw new CryptoException("Certificate parsing failed."),

			'export' => fn($certificate) =>
				openssl_x509_export($certificate, $output)
					? $output
					: throw new CryptoException("Certificate export failed."),

			'exportToFile' => fn($certificate, string $filePath) =>
				openssl_x509_export_to_file($certificate, $filePath)
					?: throw new CryptoException("Certificate export to file failed."),

				'verifyPrivateKey' => fn($certificate, $privateKey) =>
					openssl_x509_check_private_key($certificate, $privateKey),

				'checkPurpose' => fn(
					$certificate,
					int|string $purpose,
					?array $caInfo = null,
					?array $untrustedCerts = null
				) => $this->normalizeVerificationResult(
					openssl_x509_checkpurpose(
						$certificate,
						$this->isInt($purpose)
							? $purpose
							: ($this->config['x509'][$purpose]
							?? throw new CryptoException("Unsupported certificate purpose: {$purpose}.")),
						$caInfo,
						$untrustedCerts
					),
					$this->openSslFailureMessage("Certificate purpose check failed.")
				),

				'fingerprint' => fn(
					$certificate,
					?string $algo = null,
					?bool $binary = true
				) => $this->rejectFalseWithRuntimeMessage(
					openssl_x509_fingerprint(
						$certificate,
						$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
							?? throw new CryptoException("Invalid algorithm for fingerprint."),
						$binary
					),
					'Certificate fingerprint calculation failed.'
				),

				'convertPemToDer' => fn(string $pem) =>
					$this->base64DecodeString(
						$this->replaceText(
							["\n", "\r", " "],
							'',
							$this->replaceByPattern('/-----BEGIN (.+?)-----|-----END (.+?)-----/', '', $pem) ?? ''
						)
					) ?: throw new CryptoException("Invalid PEM format."),

				'convertDerToPem' => fn(string $der, string $label = 'CERTIFICATE') =>
					"-----BEGIN {$label}-----\n" . chunk_split($this->base64EncodeString($der), 64, "\n") . "-----END {$label}-----\n",

			'free' => function ($certificate): bool {
				openssl_x509_free($certificate);
				return true;
			},

			default => throw new CryptoException("Unsupported certificate action: {$action}."),
		};
	}

	public function Signer(string $type): callable
	{
		return match ($type) {
			'signData' => fn(
				string $data,
				$privateKey,
				?string $algo = null
			) => openssl_sign(
				$data,
				$signature,
				$privateKey,
				$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
					?? throw new CryptoException("Invalid algorithm for signing.")
			) ? $signature : throw new CryptoException("Signing failed."),

				'pkcs7Sign' => fn(
					string $inputFile,
					string $outputFile,
					$certificate,
					$privateKey,
					?array $headers = null,
					?int $flags = null,
					?string $untrustedCertificatesFile = null
				) => openssl_pkcs7_sign(
					$inputFile,
					$outputFile,
					$certificate,
					$privateKey,
					$headers ?? [],
					$this->config['pkcs7']['binary'] | ($flags ?? 0),
					$untrustedCertificatesFile
				) ? $outputFile : throw new CryptoException($this->openSslFailureMessage("PKCS7 signing failed.")),

				'cmsSign' => fn(
					string $inputFile,
					string $outputFile,
					$certificate,
					$privateKey,
					?array $headers = null,
					?int $flags = null,
					?int $encoding = null,
					?string $untrustedCertificatesFile = null
				) => openssl_cms_sign(
					$inputFile,
					$outputFile,
					$certificate,
					$privateKey,
					$headers ?? [],
					$this->config['cms']['binary'] | ($flags ?? 0),
					$encoding ?? $this->config['options']['encodingSmime'],
					$untrustedCertificatesFile
				) ? $outputFile : throw new CryptoException($this->openSslFailureMessage("CMS signing failed.")),

			'spkiSign' => fn($privateKey, $challenge) =>
				openssl_spki_new($privateKey, $challenge)
					?: throw new CryptoException("SPKI signing failed."),

			default => throw new CryptoException("Unsupported signing type: {$type}."),
		};
	}

	public function Verifier(string $type): callable
	{
		return match ($type) {
			'verifySignature' => fn(
				string $data,
				string $signature,
				$publicKey,
				?string $algo = null
			) => openssl_verify(
				$data,
				$signature,
				$publicKey,
				$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
					?? throw new CryptoException("Invalid algorithm for verification.")
			) === 1,

				'x509' => fn($cert, $publicKey) =>
					$this->normalizeVerificationResult(
						openssl_x509_verify($cert, $publicKey),
						$this->openSslFailureMessage("Certificate verification failed.")
					),

				'pkcs7Verify' => fn(
					string $inputFile,
					?int $flags = null,
					?string $signersCertificatesFile = null,
					?array $caInfo = null,
					?string $untrustedCertificatesFile = null,
					?string $content = null,
					?string $outputFile = null
				) => $this->normalizeVerificationResult(
					openssl_pkcs7_verify(
						$inputFile,
						$flags ?? $this->config['pkcs7']['defaultFlags'],
						$signersCertificatesFile,
						$caInfo,
						$untrustedCertificatesFile,
						$content,
						$outputFile
					),
					$this->openSslFailureMessage("PKCS7 verification failed.")
				),

				'cmsVerify' => fn(
					string $inputFile,
					?int $flags = null,
					?string $certificates = null,
					?array $caInfo = null,
					?string $untrustedCertificatesFile = null,
					?string $content = null,
					?string $pk7 = null,
					?string $sigfile = null,
					?int $encoding = null
				) => $this->normalizeVerificationResult(
					openssl_cms_verify(
						$inputFile,
						$flags ?? $this->config['cms']['defaultFlags'],
						$certificates,
						$caInfo,
						$untrustedCertificatesFile,
						$content,
						$pk7,
						$sigfile,
						$encoding ?? $this->config['options']['encodingSmime']
					),
					$this->openSslFailureMessage("CMS verification failed.")
				),

				'spkiVerify' => fn(string $spki) =>
					(bool) openssl_spki_verify($spki),

				default => throw new CryptoException("Unsupported verification type: {$type}."),
			};
		}

		public function KeyExchangeHandler(string $action): callable
		{
			return match ($action) {
					'deriveSharedKey' => function ($peerPublicKey, $privateKey): string {
						$this->requireFunction('openssl_pkey_derive', 'openssl key derivation');

						return $this->rejectFalseWithRuntimeMessage(
							openssl_pkey_derive(
								$peerPublicKey,
								$privateKey
							),
							'Key derivation failed.'
						);
					},

					'computeDhSharedSecret' => function ($publicKey, $privateKey): string {
						$this->requireFunction('openssl_dh_compute_key', 'openssl Diffie-Hellman key computation');

						return $this->rejectFalseWithRuntimeMessage(
							openssl_dh_compute_key(
								$publicKey,
								$privateKey
							),
							'Failed to compute DH shared secret.'
						);
					},

				default => throw new CryptoException("Unsupported key exchange action: {$action}."),
			};
		}

		public function CipherHandler(string $action): callable
		{
			return match ($action) {
					'getIvLength' => fn(string $cipher) =>
						openssl_cipher_iv_length(
							$this->resolveCipherMethod($cipher)
						),

						'getKeyLength' => function (string $cipher): int {
							$this->requireFunction('openssl_cipher_key_length', 'openssl cipher key length');

							return openssl_cipher_key_length(
								$this->resolveCipherMethod($cipher)
							);
						},

				'listCiphers' => fn() =>
					openssl_get_cipher_methods(),

				'seal' => fn(string $data, array $publicKeys) =>
					openssl_seal(
						$data,
						$sealed,
						$envKeys,
						$publicKeys
					) ? ['sealed' => $sealed, 'envKeys' => $envKeys]
						: throw new CryptoException("Seal encryption failed."),

				'open' => fn(string $sealedData, $privateKey, string $envKey) =>
					openssl_open(
						$sealedData,
						$output,
						$envKey,
						$privateKey
					) ? $output : throw new CryptoException("Seal decryption failed."),

				default => throw new CryptoException("Unsupported cipher action: {$action}."),
			};
		}

		public function MemoryHandler(string $action): callable
		{
			return match ($action) {
				'clearSensitiveData' => fn(string &$data) =>
					$data = $this->repeatString("\0", $this->length($data)),

				'compareSecurely' => fn(string $a, string $b) =>
					hash_equals($a, $b),

				default => throw new CryptoException("Unsupported memory action: {$action}."),
			};
		}

		public function DataConverter(string $type): callable
		{
			return match ($type) {
					'bin2base64' => fn(string $data) =>
						$this->base64EncodeString($data),

					'base642bin' => fn(string $data) =>
						$this->rejectFalse(
							$this->base64DecodeString($data, true),
							"Invalid Base64 encoded string."
						),

				'bin2hex' => fn(string $data) =>
					bin2hex($data),

					'hex2bin' => fn(string $data) =>
						$this->rejectFalse(
							hex2bin($data),
							"Invalid hexadecimal string."
						),

				default => throw new CryptoException("Unsupported conversion type: {$type}."),
			};
		}

		public function PKIHandler(string $type): callable
		{
			return match ($type) {
				'pkcs7Read' => fn(string $pkcs7File, &$certificates) =>
					openssl_pkcs7_read($pkcs7File, $certificates)
						? $certificates
						: throw new CryptoException("Failed to read PKCS7 file: {$pkcs7File}."),

				'cmsRead' => fn(string $cmsFile, &$certificates) =>
					openssl_cms_read($cmsFile, $certificates)
						? $certificates
						: throw new CryptoException("Failed to read CMS file: {$cmsFile}."),

				'pkcs12Export' => fn($certificate, $privateKey, string $password, array $args = []) =>
					openssl_pkcs12_export($certificate, $output, $privateKey, $password, $args)
						? $output
						: throw new CryptoException("PKCS#12 export failed."),

				'pkcs12ExportToFile' => fn($certificate, $privateKey, string $password, string $filePath, array $args = []) =>
					openssl_pkcs12_export_to_file($certificate, $filePath, $privateKey, $password, $args)
						?: throw new CryptoException("PKCS#12 export to file failed for path: {$filePath}."),

				'pkcs12Read' => fn(string $pkcs12, string $password) =>
					openssl_pkcs12_read($pkcs12, $certificates, $password)
						? $certificates
						: throw new CryptoException("Failed to read PKCS#12 data."),

				'pkcs7Encrypt' => function (
					string $inputFile,
					string $outputFile,
					$certificate,
					?array $headers = null,
					?int $flags = null,
					?int $cipherAlgorithm = null
				): string {
					$arguments = [
						$inputFile,
						$outputFile,
						$certificate,
						$headers ?? [],
						$flags ?? 0,
					];

					if ($cipherAlgorithm !== null) {
						$arguments[] = $cipherAlgorithm;
					}

					$success = openssl_pkcs7_encrypt(...$arguments);

					if (!$success) {
						throw new CryptoException($this->openSslFailureMessage("PKCS7 encryption failed."));
					}

					return $outputFile;
				},

				'pkcs7Decrypt' => fn(string $inputFile, string $outputFile, $certificate, $privateKey = null) =>
					openssl_pkcs7_decrypt($inputFile, $outputFile, $certificate, $privateKey)
						? $outputFile
						: throw new CryptoException($this->openSslFailureMessage("PKCS7 decryption failed.")),

				'cmsEncrypt' => fn(
					string $inputFile,
					string $outputFile,
					$certificate,
					?array $headers = null,
					?int $flags = null,
					?int $encoding = null,
					?int $cipherAlgorithm = null
				) => openssl_cms_encrypt(
					$inputFile,
					$outputFile,
					$certificate,
					$headers ?? [],
					$flags ?? 0,
					$encoding ?? $this->config['options']['encodingSmime'],
					$cipherAlgorithm
				) ? $outputFile : throw new CryptoException($this->openSslFailureMessage("CMS encryption failed.")),

				'cmsDecrypt' => fn(
					string $inputFile,
					string $outputFile,
					$certificate,
					$privateKey = null,
					?int $encoding = null
				) => openssl_cms_decrypt(
					$inputFile,
					$outputFile,
					$certificate,
					$privateKey,
					$encoding ?? $this->config['options']['encodingSmime']
				) ? $outputFile : throw new CryptoException($this->openSslFailureMessage("CMS decryption failed.")),

				'pkcs7Verify' => fn(
					string $inputFile,
					?int $flags = null,
					?string $signersCertificatesFile = null,
					?array $caInfo = null,
					?string $untrustedCertificatesFile = null,
					?string $content = null,
					?string $outputFile = null
				) => $this->normalizeVerificationResult(
					openssl_pkcs7_verify(
						$inputFile,
						$flags ?? 0,
						$signersCertificatesFile,
						$caInfo,
						$untrustedCertificatesFile,
						$content,
						$outputFile
					),
					$this->openSslFailureMessage("PKCS7 verification failed.")
				),

				'cmsVerify' => fn(
					string $inputFile,
					?int $flags = null,
					?string $certificates = null,
					?array $caInfo = null,
					?string $untrustedCertificatesFile = null,
					?string $content = null,
					?string $pk7 = null,
					?string $sigfile = null,
					?int $encoding = null
				) => $this->normalizeVerificationResult(
					openssl_cms_verify(
						$inputFile,
						$flags ?? 0,
						$certificates,
						$caInfo,
						$untrustedCertificatesFile,
						$content,
						$pk7,
						$sigfile,
						$encoding ?? $this->config['options']['encodingSmime']
					),
					$this->openSslFailureMessage("CMS verification failed.")
				),

				default => throw new CryptoException("Unsupported PKI action: {$type}."),
			};
		}

	public function SystemHandler(string $action): callable
	{
		return match ($action) {
				'getCertLocations' => fn() =>
					$this->rejectFalseWithRuntimeMessage(
						openssl_get_cert_locations(),
						'Failed to retrieve certificate locations.'
					),

				'getErrorString' => fn() =>
					openssl_error_string() ?: '',

				default => throw new CryptoException("Unsupported system action: {$action}."),
			};
	}

	private function resolveCipherMethod(string $cipher): string
	{
		$normalized = $this->toLower((string) ($this->replaceByPattern('/[^a-z0-9]/i', '', $cipher) ?? ''));

		return $this->config['ciphers'][$normalized]
			?? throw new CryptoException("Invalid cipher type: {$cipher}.");
	}

	private function buildCapabilities(): array
	{
		return [
			'extension' => extension_loaded('openssl'),
			'encrypt' => [
				'symmetric' => true,
				'asymmetric' => true,
				'privateencrypt' => true,
			],
			'decrypt' => [
				'symmetric' => true,
				'asymmetric' => true,
				'publicdecrypt' => true,
			],
			'random' => [
				'default' => true,
				'passwordsalt' => true,
				'key' => true,
				'generaterandomiv' => true,
				'securerandombytes' => true,
			],
			'hash' => [
				'pbkdf2' => true,
				'hashdigest' => true,
				'listhashmethods' => true,
			],
			'keys' => [
				'import' => true,
				'export' => true,
				'exporttofile' => true,
				'generate' => true,
				'getcurvenames' => true,
				'free' => true,
			],
			'keyexchange' => [
				'derivesharedkey' => $this->functionExists('openssl_pkey_derive'),
				'computedhsharedsecret' => $this->functionExists('openssl_dh_compute_key'),
			],
			'cipher' => [
				'getivlength' => true,
				'getkeylength' => $this->functionExists('openssl_cipher_key_length'),
				'listciphers' => true,
				'seal' => true,
				'open' => true,
			],
			'sign' => [
				'signdata' => true,
				'pkcs7sign' => $this->functionExists('openssl_pkcs7_sign'),
				'cmssign' => $this->functionExists('openssl_cms_sign'),
				'spkisign' => $this->functionExists('openssl_spki_new'),
			],
			'verify' => [
				'verifysignature' => true,
				'x509' => $this->functionExists('openssl_x509_verify'),
				'pkcs7verify' => $this->functionExists('openssl_pkcs7_verify'),
				'cmsverify' => $this->functionExists('openssl_cms_verify'),
				'spkiverify' => $this->functionExists('openssl_spki_verify'),
			],
			'certificate' => [
				'parse' => $this->functionExists('openssl_x509_parse'),
				'export' => $this->functionExists('openssl_x509_export'),
				'exporttofile' => $this->functionExists('openssl_x509_export_to_file'),
				'verifyprivatekey' => $this->functionExists('openssl_x509_check_private_key'),
				'checkpurpose' => $this->functionExists('openssl_x509_checkpurpose'),
				'fingerprint' => $this->functionExists('openssl_x509_fingerprint'),
			],
			'ciphers' => array_fill_keys($this->getKeys($this->config['ciphers']), true),
			'system' => [
				'getcertlocations' => $this->functionExists('openssl_get_cert_locations'),
				'geterrorstring' => $this->functionExists('openssl_error_string'),
			],
		];
	}

	private function exportPublicKey(mixed $key): string
	{
		$details = openssl_pkey_get_details($key);

		if (!$this->isArray($details) || !$this->keyExists($details, 'key')) {
			throw new CryptoException($this->openSslFailureMessage('Public key export failed.'));
		}

		return (string) $details['key'];
	}

	private function rejectFalseWithRuntimeMessage(mixed $result, string $message): mixed
	{
		return $this->rejectFalse($result, $this->openSslFailureMessage($message));
	}

	private function openSslFailureMessage(string $message): string
	{
		$error = $this->functionExists('openssl_error_string')
			? openssl_error_string()
			: null;

		if ($this->isString($error) && $this->trimString($error) !== '') {
			return $message . ' ' . $error;
		}

		return $message;
	}
}
