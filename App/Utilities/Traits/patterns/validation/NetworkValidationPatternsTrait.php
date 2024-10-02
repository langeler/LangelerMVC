<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait NetworkValidationPatternsTrait
{
	public array $patterns = [
		'slug' => "/^[a-z0-9]+(?:-[a-z0-9]+)*$/",
		'url_http_https' => "/^(https?:\/\/)?([\w\-]+\.)+[\w\-]+(\/[\w\-._~:\/?#\[\]@!$&'()*+,;=]*)?$/i",
		'url_with_port' => "/^(https?:\/\/)?([\w\-]+\.)+[\w\-]+(:\d+)?(\/[\w\-._~:\/?#\[\]@!$&'()*+,;=]*)?$/i",
		'url_with_query' => "/^(https?:\/\/)?([\w\-]+\.)+[\w\-]+(\/[\w\-._~:\/?#\[\]@!$&'()*+,;=]*)?(\?[a-zA-Z0-9_&=]+)?$/i",
		'ftp_url' => "/^ftp:\/\/[a-zA-Z0-9.-]+\/?.*$/",
		'google_drive_url' => "/^https:\/\/drive\.google\.com\/[a-zA-Z0-9\/]+$/",
		'dropbox_url' => "/^https:\/\/www\.dropbox\.com\/[a-zA-Z0-9\/]+$/",
		'ipv4_address' => "/^(\d{1,3}\.){3}\d{1,3}$/",
		'ipv6_address' => "/^([0-9a-fA-F]{1,4}:){7}([0-9a-fA-F]{1,4}|:)$/",
		'zip_code_us' => "/^\d{5}(-\d{4})?$/",
		'zip_code_uk' => "/^([A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2})$/i",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
