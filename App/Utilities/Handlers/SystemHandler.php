<?php

namespace App\Utilities\Handlers;

use StreamWrapper;
use Socket;

/**
 * Class SystemHandler
 *
 * Provides utility methods for shell command execution, stream handling, and socket operations.
 */
class SystemHandler
{
	// Shell Command Methods

	/**
	 * Escape a string to be used as a shell argument.
	 *
	 * @param string $arg The argument to escape.
	 * @return string The escaped argument.
	 */
	public function escapeArg(string $arg): string
	{
		return escapeshellarg($arg);
	}

	/**
	 * Escape a command string to be executed in the shell.
	 *
	 * @param string $command The command to escape.
	 * @return string The escaped command.
	 */
	public function escapeCmd(string $command): string
	{
		return escapeshellcmd($command);
	}

	/**
	 * Execute a command via the shell and return the last line of output.
	 *
	 * @param string $command The command to execute.
	 * @param array|null $output The output array to capture the result.
	 * @param int|null $returnVar The return status code.
	 * @return string The last line of the shell output.
	 */
	public function executeCmd(string $command, array &$output = null, int &$returnVar = null): string
	{
		return exec($command, $output, $returnVar);
	}

	/**
	 * Execute a shell command and pass the output directly to the browser or console.
	 *
	 * @param string $command The command to execute.
	 * @param int|null $returnVar The return status code.
	 * @return void
	 */
	public function passThruCmd(string $command, int &$returnVar = null): void
	{
		passthru($command, $returnVar);
	}

	/**
	 * Execute a shell command and return the complete output as a string.
	 *
	 * @param string $cmd The command to execute.
	 * @return string The output of the command.
	 */
	public function shellExecute(string $cmd): string
	{
		return shell_exec($cmd);
	}

	/**
	 * Execute a command via the system function.
	 *
	 * @param string $command The command to execute.
	 * @param int|null $returnVar The return status code.
	 * @return void
	 */
	public function systemCmd(string $command, int &$returnVar = null): void
	{
		system($command, $returnVar);
	}

	/**
	 * Parse command-line options.
	 *
	 * @param string $options The options string (e.g., "a:b::").
	 * @param array $longOpts Optional long options (e.g., ['opt:', 'flag']).
	 * @param int|null $optind The position of the next argument to be processed.
	 * @return array The parsed options.
	 */
	public function getOptions(string $options, array $longOpts = [], int &$optind = null): array
	{
		return getopt($options, $longOpts, $optind);
	}

	// StreamWrapper Methods

	/**
	 * Close a directory handle opened by StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return bool True on success, false on failure.
	 */
	public function closeDir(StreamWrapper $wrapper): bool
	{
		return $wrapper->dir_closedir();
	}

	/**
	 * Open a directory handle using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path of the directory to open.
	 * @param int $options The options for opening the directory.
	 * @return bool True on success, false on failure.
	 */
	public function openDir(StreamWrapper $wrapper, string $path, int $options): bool
	{
		return $wrapper->dir_opendir($path, $options);
	}

	/**
	 * Read an entry from a directory handle using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return string|false The directory entry name or false if no more entries.
	 */
	public function readDir(StreamWrapper $wrapper)
	{
		return $wrapper->dir_readdir();
	}

	/**
	 * Rewind a directory handle to the beginning using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return bool True on success, false on failure.
	 */
	public function rewindDir(StreamWrapper $wrapper): bool
	{
		return $wrapper->dir_rewinddir();
	}

	/**
	 * Create a directory using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path of the directory to create.
	 * @param int $mode The permissions for the directory.
	 * @param int $options The options for creating the directory.
	 * @return bool True on success, false on failure.
	 */
	public function makeDir(StreamWrapper $wrapper, string $path, int $mode, int $options): bool
	{
		return $wrapper->mkdir($path, $mode, $options);
	}

	/**
	 * Rename a path using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $pathFrom The current path.
	 * @param string $pathTo The new path.
	 * @return bool True on success, false on failure.
	 */
	public function renamePath(StreamWrapper $wrapper, string $pathFrom, string $pathTo): bool
	{
		return $wrapper->rename($pathFrom, $pathTo);
	}

	/**
	 * Remove a directory using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path of the directory to remove.
	 * @param int $options The options for removing the directory.
	 * @return bool True on success, false on failure.
	 */
	public function removeDir(StreamWrapper $wrapper, string $path, int $options): bool
	{
		return $wrapper->rmdir($path, $options);
	}

	// Stream Handling Methods

	/**
	 * Close a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return bool True on success, false on failure.
	 */
	public function closeStream(StreamWrapper $wrapper): bool
	{
		return $wrapper->stream_close();
	}

	/**
	 * Check if a stream has reached the end of file.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return bool True if at EOF, false otherwise.
	 */
	public function streamEof(StreamWrapper $wrapper): bool
	{
		return $wrapper->stream_eof();
	}

	/**
	 * Flush a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return bool True on success, false on failure.
	 */
	public function flushStream(StreamWrapper $wrapper): bool
	{
		return $wrapper->stream_flush();
	}

	/**
	 * Lock a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param int $operation The locking operation.
	 * @return bool True on success, false on failure.
	 */
	public function lockStream(StreamWrapper $wrapper, int $operation): bool
	{
		return $wrapper->stream_lock($operation);
	}

	/**
	 * Open a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path of the stream.
	 * @param string $mode The mode for opening the stream.
	 * @param int $options The options for opening the stream.
	 * @param string|null $openedPath The opened path reference.
	 * @return bool True on success, false on failure.
	 */
	public function openStream(StreamWrapper $wrapper, string $path, string $mode, int $options, ?string &$openedPath = null): bool
	{
		return $wrapper->stream_open($path, $mode, $options, $openedPath);
	}

	/**
	 * Read from a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param int $count The number of bytes to read.
	 * @return string The read data.
	 */
	public function readStream(StreamWrapper $wrapper, int $count): string
	{
		return $wrapper->stream_read($count);
	}

	/**
	 * Seek to a specific position in a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param int $offset The offset to seek to.
	 * @param int $whence The seek mode.
	 * @return bool True on success, false on failure.
	 */
	public function seekStream(StreamWrapper $wrapper, int $offset, int $whence = SEEK_SET): bool
	{
		return $wrapper->stream_seek($offset, $whence);
	}

	/**
	 * Get stream statistics using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @return array The stream statistics.
	 */
	public function statStream(StreamWrapper $wrapper): array
	{
		return $wrapper->stream_stat();
	}

	/**
	 * Write to a stream using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $data The data to write.
	 * @return int The number of bytes written.
	 */
	public function writeStream(StreamWrapper $wrapper, string $data): int
	{
		return $wrapper->stream_write($data);
	}

	/**
	 * Unlink (delete) a path using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path to unlink.
	 * @return bool True on success, false on failure.
	 */
	public function unlinkPath(StreamWrapper $wrapper, string $path): bool
	{
		return $wrapper->unlink($path);
	}

	/**
	 * Get statistics of a URL using StreamWrapper.
	 *
	 * @param StreamWrapper $wrapper The stream wrapper.
	 * @param string $path The path of the URL.
	 * @param int $flags The flags for getting the stats.
	 * @return array The statistics of the URL.
	 */
	public function statUrl(StreamWrapper $wrapper, string $path, int $flags): array
	{
		return $wrapper->url_stat($path, $flags);
	}

	// Socket Methods

	/**
	 * Create a socket using socket_create.
	 *
	 * @param int $domain The protocol family to be used (e.g., AF_INET).
	 * @param int $type The socket type (e.g., SOCK_STREAM).
	 * @param int $protocol The protocol to be used (e.g., IPPROTO_TCP).
	 * @return Socket The created socket.
	 */
	public function createSocket(int $domain, int $type, int $protocol): Socket
	{
		return socket_create($domain, $type, $protocol);
	}

	/**
	 * Connect a socket to an address and port.
	 *
	 * @param Socket $socket The socket.
	 * @param string $address The address to connect to.
	 * @param int $port The port to connect to.
	 * @return bool True on success, false on failure.
	 */
	public function connectSocket(Socket $socket, string $address, int $port): bool
	{
		return socket_connect($socket, $address, $port);
	}

	/**
	 * Close a socket.
	 *
	 * @param Socket $socket The socket to close.
	 * @return void
	 */
	public function closeSocket(Socket $socket): void
	{
		socket_close($socket);
	}

	/**
	 * Send data over a socket.
	 *
	 * @param Socket $socket The socket.
	 * @param string $data The data to send.
	 * @param int $length The number of bytes to send.
	 * @param int $flags The flags for sending data.
	 * @return int The number of bytes sent.
	 */
	public function sendData(Socket $socket, string $data, int $length, int $flags = 0): int
	{
		return socket_send($socket, $data, $length, $flags);
	}

	/**
	 * Receive data from a socket.
	 *
	 * @param Socket $socket The socket.
	 * @param int $length The number of bytes to receive.
	 * @param int $flags The flags for receiving data.
	 * @return string The received data.
	 */
	public function receiveData(Socket $socket, int $length, int $flags = 0): string
	{
		socket_recv($socket, $buf, $length, $flags);
		return $buf ?? '';
	}
}
