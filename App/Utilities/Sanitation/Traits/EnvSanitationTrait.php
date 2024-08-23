<?php

namespace App\Utilities\Sanitation\Traits;

trait EnvSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Sanitize environment variables to ensure they are correctly formatted.
	 *
	 * @param array $envVariables
	 * @return array
	 */
	public function sanitizeEnvVariables(array $envVariables): array
	{
		return $this->sanitizeNestedData($envVariables);
	}

	/**
	 * Clean and normalize system configuration files.
	 *
	 * @param array $config
	 * @return array
	 */
	public function sanitizeSystemConfig(array $config): array
	{
		return $this->sanitizeNestedData($config);
	}

	/**
	 * Secure dynamic configurations that change at runtime.
	 *
	 * @param array $config
	 * @return array
	 */
	public function sanitizeDynamicConfig(array $config): array
	{
		return $this->sanitizeSystemConfig($config);
	}

	/**
	 * Sanitize environment-specific security settings.
	 *
	 * @param array $securitySettings
	 * @return array
	 */
	public function sanitizeEnvSecuritySettings(array $securitySettings): array
	{
		return $this->sanitizeEnvVariables($securitySettings);
	}

	/**
	 * Sanitize configurations for containerized environments.
	 *
	 * @param array $config

 * @return array
	  */
	 public function sanitizeContainerConfig(array $config): array
	 {
		 return $this->sanitizeSystemConfig($config);
	 }
}
