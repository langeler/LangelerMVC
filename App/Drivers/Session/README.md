# Session Drivers

LangelerMVC currently ships the following session drivers:

- `FileSessionDriver`
- `DatabaseSessionDriver`
- `RedisSessionDriver`
- `EncryptedSessionDriver` as a wrapper that adds at-rest payload encryption on top of any supported handler

These drivers are resolved through `App\Utilities\Managers\Data\SessionManager`, which normalizes session config and applies the correct runtime handler for the active framework session driver.
