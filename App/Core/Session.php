<?php

namespace App\Core;

use RuntimeException;
use Exception;
use App\Helpers\Sanitizer;
use App\Helpers\Validator;

/**
 * Class Session
 *
 * Handles session management, including security measures, session data encryption,
 * and validation to protect against session hijacking and fixation attacks.
 *
 * @package App\Core
 */
class Session {
    /**
     * @var string The namespace for session variables to avoid conflicts.
     */
    private $namespace;

    /**
     * @var Sanitizer Instance of the Sanitizer helper class.
     */
    private $sanitizer;

    /**
     * @var Validator Instance of the Validator helper class.
     */
    private $validator;

    /**
     * @var string The encryption key used to encrypt and decrypt session data.
     */
    private $encryptionKey;

    /**
     * @var string The cipher method used for encryption and decryption.
     */
    private $cipherMethod = 'AES-256-CBC';

    /**
     * Constructor to initialize the Session with namespace and start the session if not already started.
     *
     * @param string $namespace The namespace for session variables.
     * @param string|null $encryptionKey The encryption key for securing session data (optional).
     */
    public function __construct($namespace = 'app', $encryptionKey = null) {
        $this->namespace = $namespace;
        $this->sanitizer = new Sanitizer();
        $this->validator = new Validator();

        // Generate an encryption key if none is provided
        if (empty($encryptionKey)) {
            $encryptionKey = bin2hex(random_bytes(32)); // Generate a secure random key
        }
        $this->encryptionKey = hash('sha256', $encryptionKey);

        if (session_status() == PHP_SESSION_NONE) {
            $this->startSession();
        }

        // Ensure default session variables are set
        if (!$this->has('user_ip')) {
            $this->set('user_ip', $_SERVER['REMOTE_ADDR']);
        }

        if (!$this->has('user_agent')) {
            $this->set('user_agent', $_SERVER['HTTP_USER_AGENT']);
        }

        // Regenerate session ID periodically
        if (!$this->has('regen_time')) {
            $this->set('regen_time', time());
        } elseif ($this->get('regen_time') < time() - 300) { // 5 minutes
            $this->regenerateSession();
            $this->set('regen_time', time());
        }

        $this->validateSession();
    }

    /**
     * Start the session with secure settings.
     *
     * Configures the session cookie parameters to enhance security and starts the session.
     */
    private function startSession() {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();

        // Prevent session fixation attacks
        if (!$this->has('initiated')) {
            $this->regenerateSession();
            $this->set('initiated', true);
        }
    }

    /**
     * Regenerate session ID to prevent session fixation.
     *
     * Regenerates the session ID and deletes the old session file.
     */
    private function regenerateSession() {
        session_regenerate_id(true);
    }

    /**
     * Validate session to prevent hijacking by checking IP and User-Agent.
     *
     * Validates that the session is still associated with the correct IP address and User-Agent.
     * If validation fails, the session is destroyed and an exception is thrown.
     *
     * @throws RuntimeException if session validation fails.
     */
    private function validateSession() {
        if ($_SERVER['REMOTE_ADDR'] !== $this->get('user_ip') || $_SERVER['HTTP_USER_AGENT'] !== $this->get('user_agent')) {
            $this->destroy();
            throw new RuntimeException('Session validation failed.');
        }
    }

    /**
     * Encrypt data.
     *
     * Encrypts the provided data using the defined cipher method and encryption key.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted data.
     */
    private function encrypt(string $data): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipherMethod));
        $encryptedData = openssl_encrypt($data, $this->cipherMethod, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encryptedData);
    }

    /**
     * Decrypt data.
     *
     * Decrypts the provided encrypted data using the defined cipher method and encryption key.
     *
     * @param string $data The data to decrypt.
     * @return string The decrypted data.
     */
    private function decrypt(string $data): string {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipherMethod);
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);
        return openssl_decrypt($encryptedData, $this->cipherMethod, $this->encryptionKey, 0, $iv);
    }

    /**
     * Get a namespaced session key.
     *
     * Combines the namespace with the provided key to create a unique session key.
     *
     * @param string $key The key to namespace.
     * @return string The namespaced session key.
     */
    private function getNamespacedKey(string $key): string {
        return $this->namespace . '_' . $key;
    }

    /**
     * Set a session variable.
     *
     * Stores an encrypted session variable under the provided key.
     *
     * @param string $key The key under which to store the session variable.
     * @param mixed $value The value to store.
     */
    public function set(string $key, $value): void {
        $encryptedValue = $this->encrypt(serialize($value));
        $_SESSION[$this->getNamespacedKey($key)] = $encryptedValue;
    }

    /**
     * Get a session variable.
     *
     * Retrieves and decrypts a session variable by its key.
     *
     * @param string $key The key of the session variable to retrieve.
     * @return mixed The decrypted session variable, or null if not set.
     */
    public function get(string $key) {
        $encryptedValue = $_SESSION[$this->getNamespacedKey($key)] ?? null;
        if ($encryptedValue === null) {
            return null;
        }
        return unserialize($this->decrypt($encryptedValue));
    }

    /**
     * Check if a session variable exists.
     *
     * Checks whether a session variable is set for the given key.
     *
     * @param string $key The key of the session variable to check.
     * @return bool True if the session variable exists, false otherwise.
     */
    public function has(string $key): bool {
        return isset($_SESSION[$this->getNamespacedKey($key)]);
    }

    /**
     * Remove a session variable.
     *
     * Unsets the session variable associated with the provided key.
     *
     * @param string $key The key of the session variable to remove.
     */
    public function remove(string $key): void {
        $namespacedKey = $this->getNamespacedKey($key);
        if (isset($_SESSION[$namespacedKey])) {
            unset($_SESSION[$namespacedKey]);
        }
    }

    /**
     * Destroy the session.
     *
     * Clears all session variables and destroys the session.
     */
    public function destroy(): void {
        session_unset();
        session_destroy();
    }

    /**
     * Set session expiry time.
     *
     * Sets the expiry time for the session in seconds from the current time.
     *
     * @param int $expiryTimeInSeconds The number of seconds until the session expires.
     */
    public function setExpiry(int $expiryTimeInSeconds): void {
        $this->set('expiry_time', time() + $expiryTimeInSeconds);
    }

    /**
     * Check if the session is expired.
     *
     * Determines whether the session has expired based on the expiry time.
     *
     * @return bool True if the session has expired, false otherwise.
     */
    public function isExpired(): bool {
        $expiryTime = $this->get('expiry_time');
        return $expiryTime !== null && time() > $expiryTime;
    }

    /**
     * Clear the session if it is expired.
     *
     * Destroys the session if it has expired.
     */
    public function clearExpiredSessions(): void {
        if ($this->isExpired()) {
            $this->destroy();
        }
    }
}
?>
