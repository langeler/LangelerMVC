<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait NetworkSanitationPatternsTrait
{
	public array $patterns = [
		'slug' => '/[^a-z0-9\-]/',
		'url_http_https' => '/[^a-zA-Z0-9\-_\.~:\/?#\[\]@!$&\'()*+,;=%]/',
		'url_with_port' => '/[^a-zA-Z0-9\-_\.~:\/?#\[\]@!$&\'()*+,;=%]/',
		'url_with_query' => '/[^a-zA-Z0-9\-_\.~:\/?#\[\]@!$&\'()*+,;=%]/',
		'ftp_url' => '/[^a-zA-Z0-9\-_\.~:\/]/',
		'google_drive_url' => '/[^a-zA-Z0-9\/]/',
		'dropbox_url' => '/[^a-zA-Z0-9\/]/',
		'ipv4_address' => '/[^0-9.]/',
		'ipv6_address' => '/[^0-9a-fA-F:]/',
	];

	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

