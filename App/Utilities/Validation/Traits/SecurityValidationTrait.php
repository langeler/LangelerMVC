<?php

namespace App\Utilities\Validation\Traits;

trait SecurityValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a
password is strong.
	 *
	 * @param string $password
	 * @return bool
	 */
	 public function validateStrongPassword(string $password): bool
	 {
	 return $this->validatePattern($password, ’/^(?=.[A-Z])(?=.[a-z])(?=.\d)(?=.[@$!%?&#])[A-Za-z\d@$!%*?&#]{8,}$/’);
	 }

	 /**
	  * Validate that an access control list (ACL) contains a specific role.
	  *
	  * @param array $acl
	  * @param string $role
	  * @return bool
	  */
	 public function validateAccessControl(array $acl, string $role): bool
	 {
		 return in_array($role, $acl);
	 }

	 /**
	  * Validate that an encryption key is of the correct length.
	  *
	  * @param string $encryptionKey
	  * @return bool
	  */
	 public function validateEncryptionKey(string $encryptionKey): bool
	 {
		 return $this->validateLength($encryptionKey, 32, 32);
	 }
}
