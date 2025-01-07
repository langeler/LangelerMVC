<?php

namespace App\Drivers\Crypto;

use App\Abstracts\Data\Crypto;
use App\Contracts\Data\CryptoInterface;

class OpenSSLCrypto extends Crypto implements CryptoInterface
{
	protected readonly array $config;

	public function __construct()
	{
		$this->config = [
			'ciphers' => [
				'aes128cbc' => OPENSSL_CIPHER_AES_128_CBC,
				'aes192cbc' => OPENSSL_CIPHER_AES_192_CBC,
				'aes256cbc' => OPENSSL_CIPHER_AES_256_CBC,
				'aes128gcm' => OPENSSL_CIPHER_AES_128_GCM,
				'aes256gcm' => OPENSSL_CIPHER_AES_256_GCM,
				'rc2_40' => OPENSSL_CIPHER_RC2_40,
				'rc2_128' => OPENSSL_CIPHER_RC2_128,
				'rc2_64' => OPENSSL_CIPHER_RC2_64,
				'des' => OPENSSL_CIPHER_DES,
				'3des' => OPENSSL_CIPHER_3DES,
			],
			'algorithms' => [
				'sha1' => OPENSSL_ALGO_SHA1,
				'md5' => OPENSSL_ALGO_MD5,
				'sha256' => OPENSSL_ALGO_SHA256,
				'sha512' => OPENSSL_ALGO_SHA512,
				'sha224' => OPENSSL_ALGO_SHA224,
				'sha384' => OPENSSL_ALGO_SHA384,
				'sha3_256' => OPENSSL_ALGO_SHA3_256,
				'sha3_512' => OPENSSL_ALGO_SHA3_512,
				'sha3_224' => OPENSSL_ALGO_SHA3_224,
				'sha3_384' => OPENSSL_ALGO_SHA3_384,
				'sm3' => OPENSSL_ALGO_SM3,
				'rmd160' => OPENSSL_ALGO_RMD160,
				'md4' => OPENSSL_ALGO_MD4,
				'dss1' => OPENSSL_ALGO_DSS1,
				'md2' => OPENSSL_ALGO_MD2,
				'blake2b512' => OPENSSL_ALGO_BLAKE2B512,
				'blake2s256' => OPENSSL_ALGO_BLAKE2S256,
			],
			'keyTypes' => [
				'rsa' => OPENSSL_KEYTYPE_RSA,
				'dsa' => OPENSSL_KEYTYPE_DSA,
				'dh' => OPENSSL_KEYTYPE_DH,
				'ec' => OPENSSL_KEYTYPE_EC,
			],
			'padding' => [
				'noPadding' => OPENSSL_NO_PADDING,
				'pkcs1' => OPENSSL_PKCS1_PADDING,
				'sslv23' => OPENSSL_SSLV23_PADDING,
				'pkcs1Oaep' => OPENSSL_PKCS1_OAEP_PADDING,
			],
			'pkcs7' => [
				'detached' => PKCS7_DETACHED,
				'text' => PKCS7_TEXT,
				'noIntern' => PKCS7_NOINTERN,
				'noVerify' => PKCS7_NOVERIFY,
				'noChain' => PKCS7_NOCHAIN,
				'noSigs' => PKCS7_NOSIGS,
				'noAttr' => PKCS7_NOATTR,
				'binary' => PKCS7_BINARY,
				'noCerts' => PKCS7_NOCERTS,
				'noCrl' => PKCS7_NOCRL,
				'encrypt' => PKCS7_ENCRYPT,
				'signed' => PKCS7_SIGNED,
				'envelope' => PKCS7_ENVELOPE,
				'signedEnvelope' => PKCS7_SIGNED_ENVELOPE,
				'noOldMimeType' => PKCS7_NOOLDMIMETYPE,  // PHP 8.3+ only
			],
			'cms' => [
				'text' => OPENSSL_CMS_TEXT,
				'binary' => OPENSSL_CMS_BINARY,
				'noIntern' => OPENSSL_CMS_NOINTERN,
				'noVerify' => OPENSSL_CMS_NOVERIFY,
				'noCerts' => OPENSSL_CMS_NOCERTS,
				'noAttr' => OPENSSL_CMS_NOATTR,
				'detached' => OPENSSL_CMS_DETACHED,
				'noSigs' => OPENSSL_CMS_NOSIGS,
				'oldMimeType' => OPENSSL_CMS_OLDMIMETYPE,  // PHP 8.3+ only
			],
			'ssl' => [
				'tlsv1' => OPENSSL_TLSV1,
				'tlsv1_1' => OPENSSL_TLSV1_1,
				'tlsv1_2' => OPENSSL_TLSV1_2,
				'tlsv1_3' => OPENSSL_TLSV1_3,
				'sslv2Server' => OPENSSL_SSLV2_SERVER_METHOD,
				'sslv3Server' => OPENSSL_SSLV3_SERVER_METHOD,
				'tlsv1Server' => OPENSSL_TLSV1_SERVER_METHOD,
				'tlsv1_1Server' => OPENSSL_TLSV1_1_SERVER_METHOD,
				'tlsv1_2Server' => OPENSSL_TLSV1_2_SERVER_METHOD,
				'tlsv1_3Server' => OPENSSL_TLSV1_3_SERVER_METHOD,
			],
			'options' => [
				'rawData' => OPENSSL_RAW_DATA,
				'zeroPadding' => OPENSSL_ZERO_PADDING,
				'pkcs1Padding' => OPENSSL_PKCS1_PADDING,
				'dontZeroPadKey' => OPENSSL_DONT_ZERO_PAD_KEY,
				'dontVerifyPeer' => OPENSSL_DONT_VERIFY_PEER,
				'sslCompression' => OPENSSL_SSL_COMPRESSION,
				'encodingSmime' => OPENSSL_ENCODING_SMIME,
				'encodingDer' => OPENSSL_ENCODING_DER,
				'encodingPem' => OPENSSL_ENCODING_PEM,
				'defaultStreamCryptoMethod' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
			],
			'x509' => [
				'purposeSSLClient' => X509_PURPOSE_SSL_CLIENT,
				'purposeSSLServer' => X509_PURPOSE_SSL_SERVER,
				'purposeNSClient' => X509_PURPOSE_NS_SSL_CLIENT,
				'purposeNSServer' => X509_PURPOSE_NS_SSL_SERVER,
				'purposeSMIMESign' => X509_PURPOSE_SMIME_SIGN,
				'purposeSMIMEEncrypt' => X509_PURPOSE_SMIME_ENCRYPT,
				'purposeCRLSign' => X509_PURPOSE_CRL_SIGN,
				'purposeAny' => X509_PURPOSE_ANY,
			],
			'streamCryptoMethods' => [
				'sslv3Client' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
				'sslv3Server' => STREAM_CRYPTO_METHOD_SSLv3_SERVER,
				'tlsv1Client' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
				'tlsv1Server' => STREAM_CRYPTO_METHOD_TLSv1_0_SERVER,
				'tlsv1_1Client' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
				'tlsv1_1Server' => STREAM_CRYPTO_METHOD_TLSv1_1_SERVER,
				'tlsv1_2Client' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
				'tlsv1_2Server' => STREAM_CRYPTO_METHOD_TLSv1_2_SERVER,
				'tlsv1_3Client' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
				'tlsv1_3Server' => STREAM_CRYPTO_METHOD_TLSv1_3_SERVER,
			],
			'pkcs12' => [
				'default' => PKCS12_DEFAULT,
				'noCerts' => PKCS12_NO_CERTS,
				'includeCertChain' => PKCS12_INCLUDE_CERT_CHAIN,
			],
			'version' => [
				'text' => OPENSSL_VERSION_TEXT,
				'number' => OPENSSL_VERSION_NUMBER,
			],
			'sni' => [
				'tlsextServerName' => OPENSSL_TLSEXT_SERVER_NAME,
			]
		];
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
			) => openssl_encrypt(
				$data,
				$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher type: {$cipher}"),
				$key ?? $this->config['keys']['defaultKey']
					?? throw new CryptoException("Encryption key is required."),
				$options ?? $this->config['options']['rawData'],
				$iv ?? $this->RandomGenerator('generateRandomIv')($cipher)
			) ?: throw new CryptoException("Symmetric encryption failed."),

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
			) => openssl_decrypt(
				$data,
				$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher type: {$cipher}"),
				$key ?? $this->config['keys']['defaultKey']
					?? throw new CryptoException("Decryption key is required."),
				$options ?? $this->config['options']['rawData'],
				$iv ?? throw new CryptoException("IV is required for symmetric decryption.")
			) ?: throw new CryptoException("Symmetric decryption failed."),

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

	public function RandomGenerator(string $type): callable
	{
		return match ($type) {
			'default' => fn(?int $length = null) =>
				random_bytes($length ?? $this->config['random']['defaultLength'] ?? 32),

			'passwordSalt' => fn(?int $length = null) =>
				random_bytes($length ?? $this->config['pwHash']['saltBytes'] ?? 16),

			'key' => fn(?int $length = null) =>
				random_bytes($length ?? $this->config['keys']['defaultLength'] ?? 32),

			'generateRandomIv' => fn(string $cipher) =>
				random_bytes(
					openssl_cipher_iv_length(
						$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher type: {$cipher}.")
					)
				),

			'secureRandomBytes' => fn(?int $length = null) => [
				'bytes' => openssl_random_pseudo_bytes(
					$length ?? $this->config['random']['defaultPseudoRandomLength'] ?? 32,
					$isStrong
				),
				'isStrong' => $isStrong,
			],

			default => throw new CryptoException("Unsupported random byte generation type: {$type}."),
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
				openssl_digest(
					$data,
					$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
						?? throw new CryptoException("Invalid algorithm specified for digest.")
				) ?: throw new CryptoException("Digest computation failed."),

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
				'private' => openssl_pkey_get_private($key, $passphrase)
					?: throw new CryptoException("Private key import failed."),
				'public' => openssl_pkey_get_public($key)
					?: throw new CryptoException("Public key import failed."),
				default => throw new CryptoException("Unsupported key import type: {$type}."),
			},

			'export' => fn(string $type, $key, ?string $passphrase = null) => match ($type) {
				'private' => openssl_pkey_export($key, $output, $passphrase)
					? $output
					: throw new CryptoException("Private key export failed."),
				'public' => openssl_pkey_get_details($key)['key']
					?? throw new CryptoException("Public key export failed."),
				default => throw new CryptoException("Unsupported key export type: {$type}."),
			},

			'exportToFile' => fn($key, string $filePath, ?string $passphrase = null) =>
				openssl_pkey_export_to_file($key, $filePath, $passphrase)
					?: throw new CryptoException("Key export to file failed."),

			'generate' => fn(?string $type = null, ?int $bits = null, ?string $curve = null) => match ($type ?? 'rsa') {
				'rsa' => openssl_pkey_new([
					'private_key_type' => $this->config['keyTypes']['rsa'],
					'private_key_bits' => $bits ?? $this->config['keys']['defaultBits'] ?? 2048,
				]),
				'ec' => openssl_pkey_new([
					'private_key_type' => $this->config['keyTypes']['ec'],
					'curve_name' => $curve ?? $this->config['keys']['defaultCurve'] ?? 'prime256v1',
				]),
				default => throw new CryptoException("Unsupported key type: {$type}."),
			},

			'getCurveNames' => fn() =>
				openssl_get_curve_names()
					?: throw new CryptoException("Failed to retrieve curve names."),

			'free' => fn($key) =>
				openssl_pkey_free($key)
					?: throw new CryptoException("Failed to free key resource."),

			default => throw new CryptoException("Unsupported key handler action: {$action}."),
		};
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
				openssl_x509_check_private_key($certificate, $privateKey)
					?: throw new CryptoException("Private key verification failed."),

			'checkPurpose' => fn(
				$certificate,
				int $purpose,
				?array $caInfo = null,
				?array $untrustedCerts = null
			) => openssl_x509_checkpurpose(
				$certificate,
				$this->config['x509']['purpose' . ucfirst($purpose)]
					?? throw new CryptoException("Unsupported certificate purpose: {$purpose}."),
				$caInfo,
				$untrustedCerts
			) ?: throw new CryptoException("Certificate purpose check failed."),

			'fingerprint' => fn(
				$certificate,
				?string $algo = null,
				?bool $binary = true
			) => openssl_x509_fingerprint(
				$certificate,
				$this->config['algorithms'][$algo ?? $this->config['defaultAlgo']]
					?? throw new CryptoException("Invalid algorithm for fingerprint."),
				$binary
			) ?: throw new CryptoException("Certificate fingerprint calculation failed."),

			'convertPemToDer' => fn(string $pem) =>
				base64_decode(
					str_replace(["\n", "\r", " "], '', preg_replace('/-----BEGIN (.+?)-----|-----END (.+?)-----/', '', $pem))
				) ?: throw new CryptoException("Invalid PEM format."),

			'convertDerToPem' => fn(string $der, string $label = 'CERTIFICATE') =>
				"-----BEGIN {$label}-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END {$label}-----\n",

			'free' => fn($certificate) =>
				openssl_x509_free($certificate)
					?: throw new CryptoException("Failed to free certificate resource."),

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
				string $data,
				$certs,
				$privateKey,
				?int $flags = null
			) => openssl_pkcs7_sign(
				$data,
				$certs,
				$privateKey,
				null,
				$this->config['pkcs7']['binary'] | ($flags ?? 0)
			) ?: throw new CryptoException("PKCS7 signing failed."),

			'cmsSign' => fn(
				string $data,
				$privateKey,
				$certs,
				?int $flags = null
			) => openssl_cms_sign(
				$data,
				$privateKey,
				$certs,
				$this->config['cms']['binary'] | ($flags ?? 0)
			) ?: throw new CryptoException("CMS signing failed."),

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
				openssl_x509_verify($cert, $publicKey)
					?: throw new CryptoException("Certificate verification failed."),

			'pkcs7Verify' => fn(
				string $data,
				$certs,
				?int $flags = null
			) => openssl_pkcs7_verify(
				$data,
				$flags ?? $this->config['pkcs7']['defaultFlags'],
				null,
				$certs
			) ?: throw new CryptoException("PKCS7 verification failed."),

			'cmsVerify' => fn(
				string $data,
				$certs,
				?int $flags = null
			) => openssl_cms_verify(
				$data,
				$flags ?? $this->config['cms']['defaultFlags'],
				$certs
			) ?: throw new CryptoException("CMS verification failed."),

			'spkiVerify' => fn(string $spki) =>
				openssl_spki_verify($spki)
					?: throw new CryptoException("SPKI verification failed."),

			default => throw new CryptoException("Unsupported verification type: {$type}."),
		};
	}

	public function KeyExchangeHandler(string $action): callable
	{
		return match ($action) {
			'deriveSharedKey' => fn($peerPublicKey, $privateKey) =>
				openssl_pkey_derive(
					$peerPublicKey,
					$privateKey
				) ?: throw new CryptoException("Key derivation failed."),

			'computeDhSharedSecret' => fn($publicKey, $privateKey) =>
				openssl_dh_compute_key(
					$publicKey,
					$privateKey
				) ?: throw new CryptoException("Failed to compute DH shared secret."),

			default => throw new CryptoException("Unsupported key exchange action: {$action}."),
		};
	}

	public function CipherHandler(string $action): callable
	{
		return match ($action) {
			'getIvLength' => fn(string $cipher) =>
				openssl_cipher_iv_length(
					$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher type: {$cipher}")
				),

			'getKeyLength' => fn(string $cipher) =>
				openssl_cipher_key_length(
					$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher type: {$cipher}")
				),

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
				$data = str_repeat("\0", strlen($data)),

			'compareSecurely' => fn(string $a, string $b) =>
				hash_equals($a, $b),

			default => throw new CryptoException("Unsupported memory action: {$action}."),
		};
	}

	public function DataConverter(string $type): callable
	{
		return match ($type) {
			'bin2base64' => fn(string $data) =>
				base64_encode($data),

			'base642bin' => fn(string $data) =>
				base64_decode($data, true)
					?: throw new CryptoException("Invalid Base64 encoded string."),

			'bin2hex' => fn(string $data) =>
				bin2hex($data),

			'hex2bin' => fn(string $data) =>
				hex2bin($data)
					?: throw new CryptoException("Invalid hexadecimal string."),

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

			'pkcs7Encrypt' => fn($data, array $certs, ?int $flags = null) =>
				openssl_pkcs7_encrypt(
					$data,
					$certs,
					$this->config['pkcs7']['binary'] | ($flags ?? 0)
				) ?: throw new CryptoException("PKCS7 encryption failed."),

			'pkcs7Decrypt' => fn($data, $privateKey, $cert) =>
				openssl_pkcs7_decrypt($data, $privateKey, $cert)
					?: throw new CryptoException("PKCS7 decryption failed."),

			'cmsEncrypt' => fn($data, $certs, string $cipher, ?int $flags = null) =>
				openssl_cms_encrypt(
					$data,
					$certs,
					$this->config['ciphers'][$cipher] ?? throw new CryptoException("Invalid cipher: {$cipher}."),
					$flags ?? 0
				) ?: throw new CryptoException("CMS encryption failed."),

			'cmsDecrypt' => fn($data, $privateKey, $certificate, ?int $flags = null) =>
				openssl_cms_decrypt($data, $privateKey, $certificate, $flags ?? 0)
					?: throw new CryptoException("CMS decryption failed."),

			'pkcs7Verify' => fn($data, array $certs, ?int $flags = null) =>
				openssl_pkcs7_verify($data, $flags ?? 0, null, $certs)
					?: throw new CryptoException("PKCS7 verification failed."),

			'cmsVerify' => fn($data, array $certs, ?int $flags = null) =>
				openssl_cms_verify($data, $flags ?? 0, $certs)
					?: throw new CryptoException("CMS verification failed."),

			default => throw new CryptoException("Unsupported PKI action: {$type}."),
		};
	}

	public function SystemHandler(string $action): callable
	{
		return match ($action) {
			'getCertLocations' => fn() =>
				openssl_get_cert_locations()
					?: throw new CryptoException("Failed to retrieve certificate locations."),

			'getErrorString' => fn() =>
				openssl_error_string()
					?: throw new CryptoException("No OpenSSL error string available."),

			default => throw new CryptoException("Unsupported system action: {$action}."),
		};
	}
}

