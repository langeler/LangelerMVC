<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Data;

use RuntimeException;
use SessionHandler;

/**
 * Manages low-level PHP session handler interactions.
 */
class SessionManager
{
    /**
     * Create a new SessionHandler instance.
     */
    public function createHandler(): SessionHandler
    {
        return new SessionHandler();
    }

    /**
     * Register a custom PHP session handler when runtime conditions allow it.
     *
     * Native SessionHandler does not need explicit registration because PHP
     * already uses it by default. Custom subclasses still can be registered.
     */
    public function registerHandler(SessionHandler $handler, bool $registerShutdown = true): bool
    {
        if (!$this->requiresCustomRegistration($handler)) {
            return true;
        }

        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('Cannot register a custom session handler after the session has started.');
        }

        if (headers_sent($file, $line)) {
            $location = $file !== '' ? sprintf(' (%s:%d)', $file, $line) : '';

            throw new RuntimeException(
                sprintf('Cannot register a custom session handler after headers have been sent%s.', $location)
            );
        }

        return session_set_save_handler($handler, $registerShutdown);
    }

    /**
     * Determine whether the provided handler differs from PHP's native default.
     */
    public function requiresCustomRegistration(SessionHandler $handler): bool
    {
        return $handler::class !== SessionHandler::class;
    }

    /**
     * Read session data by session ID.
     */
    public function read(SessionHandler $handler, string $sessionId): string
    {
        return $handler->read($sessionId);
    }

    /**
     * Write session data to the session storage.
     */
    public function write(SessionHandler $handler, string $sessionId, string $data): bool
    {
        return $handler->write($sessionId, $data);
    }

    /**
     * Destroy a session by session ID.
     */
    public function destroy(SessionHandler $handler, string $sessionId): bool
    {
        return $handler->destroy($sessionId);
    }

    /**
     * Cleanup old session data based on the max lifetime.
     */
    public function cleanup(SessionHandler $handler, int $maxLifetime): bool
    {
        return $handler->gc($maxLifetime);
    }

    /**
     * Open a session.
     */
    public function open(SessionHandler $handler, string $savePath, string $sessionName): bool
    {
        return $handler->open($savePath, $sessionName);
    }

    /**
     * Close the session.
     */
    public function close(SessionHandler $handler): bool
    {
        return $handler->close();
    }
}
