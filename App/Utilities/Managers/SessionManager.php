<?php

namespace App\Utilities\Managers;

use SessionHandler;

/**
 * Class SessionManager
 *
 * Provides utility methods for managing sessions using a custom session handler.
 */
class SessionManager
{
	// Session Handler Methods

	/**
	 * Create a new SessionHandler instance.
	 *
	 * @return SessionHandler The created SessionHandler instance.
	 */
	public function createHandler(): SessionHandler
	{
		return new SessionHandler();
	}

	/**
	 * Read session data by session ID.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @param string $sessionId The session ID.
	 * @return string The session data.
	 */
	public function read(SessionHandler $handler, string $sessionId): string
	{
		return $handler->read($sessionId);
	}

	/**
	 * Write session data to the session storage.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @param string $sessionId The session ID.
	 * @param string $data The session data to write.
	 * @return bool True on success, false on failure.
	 */
	public function write(SessionHandler $handler, string $sessionId, string $data): bool
	{
		return $handler->write($sessionId, $data);
	}

	/**
	 * Destroy a session by session ID.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @param string $sessionId The session ID.
	 * @return bool True on success, false on failure.
	 */
	public function destroy(SessionHandler $handler, string $sessionId): bool
	{
		return $handler->destroy($sessionId);
	}

	/**
	 * Cleanup old session data based on the max lifetime.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @param int $maxLifetime The maximum session lifetime in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function cleanup(SessionHandler $handler, int $maxLifetime): bool
	{
		return $handler->gc($maxLifetime);
	}

	/**
	 * Open a session.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @param string $savePath The path where session data will be saved.
	 * @param string $sessionName The name of the session.
	 * @return bool True on success, false on failure.
	 */
	public function open(SessionHandler $handler, string $savePath, string $sessionName): bool
	{
		return $handler->open($savePath, $sessionName);
	}

	/**
	 * Close the session.
	 *
	 * @param SessionHandler $handler The SessionHandler instance.
	 * @return bool True on success, false on failure.
	 */
	public function close(SessionHandler $handler): bool
	{
		return $handler->close();
	}
}
