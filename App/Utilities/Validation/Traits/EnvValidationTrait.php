<?php

namespace App\Utilities\Validation\Traits;

trait EnvValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that environment variables contain a specific key.
	 *
	 * @param array $envVariables
	 * @param string $key
	 * @return bool
	 */
	public function validateEnvVariableExists(array $envVariables, string $key): bool
	{
		return $this->validateExists($envVariables[$key] ?? null);
	}

	/**
	 * Validate that environment configuration contains all required keys.
	 *
	 * @param array $config
	 * @param array $requiredKeys
	 * @return bool
	 */
	public function validateEnvConfig(array $config, array $requiredKeys): bool
	{
		foreach ($requiredKeys as $key) {
			if (!$this->validateExists($config[$key] ?? null)) {
				return false;
			}
		}
		return true;
	}
}
