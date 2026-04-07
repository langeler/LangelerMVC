# Native PHP To Trait Consistency Audit

This document audits framework classes under `App/` and highlights direct native PHP calls that already have a trait-level wrapper elsewhere in the framework.

## Snapshot

- Class files scanned: `104`
- Class files with at least one replacement candidate: `28`
- Total native-call occurrences matching existing trait wrappers: `134`
- Low-friction replacement paths (`already-composed`): `0`
- Structural replacement paths (`available-via-trait`): `150`

## Reading Notes

- `already-composed` means the class already uses the trait that exposes the wrapper method, so replacement is low-friction.
- `available-via-trait` means the wrapper exists in the framework, but the class does not currently compose that trait.
- This audit only covers global native PHP calls that have an obvious existing trait wrapper. It does not try to replace every language construct or object method.

## Top Native Calls With Existing Trait Replacements

- `is_array`: `15` occurrence(s)
- `trim`: `12` occurrence(s)
- `strtolower`: `10` occurrence(s)
- `base64_decode`: `7` occurrence(s)
- `base64_encode`: `7` occurrence(s)
- `is_int`: `7` occurrence(s)
- `is_string`: `7` occurrence(s)
- `json_encode`: `6` occurrence(s)
- `preg_replace`: `6` occurrence(s)
- `str_replace`: `6` occurrence(s)
- `json_decode`: `5` occurrence(s)
- `substr`: `5` occurrence(s)
- `array_map`: `4` occurrence(s)
- `array_replace`: `4` occurrence(s)
- `method_exists`: `4` occurrence(s)
- `array_key_exists`: `3` occurrence(s)
- `in_array`: `3` occurrence(s)
- `array_key_first`: `2` occurrence(s)
- `array_reduce`: `2` occurrence(s)
- `function_exists`: `2` occurrence(s)
- `is_object`: `2` occurrence(s)
- `is_scalar`: `2` occurrence(s)
- `preg_match`: `2` occurrence(s)
- `str_starts_with`: `2` occurrence(s)
- `array_keys`: `1` occurrence(s)
- `array_merge`: `1` occurrence(s)
- `array_pop`: `1` occurrence(s)
- `array_values`: `1` occurrence(s)
- `preg_quote`: `1` occurrence(s)
- `preg_split`: `1` occurrence(s)
- `strtoupper`: `1` occurrence(s)
- `urldecode`: `1` occurrence(s)
- `urlencode`: `1` occurrence(s)

## Top Classes By Replacement Opportunity

- `App/Modules/WebModule/Requests/WebRequest.php`: `18` native-call occurrence(s), `0` low-friction replacement path(s), `19` structural replacement path(s)
- `App/Utilities/Handlers/DataHandler.php`: `14` native-call occurrence(s), `0` low-friction replacement path(s), `14` structural replacement path(s)
- `App/Utilities/Managers/System/FileManager.php`: `9` native-call occurrence(s), `0` low-friction replacement path(s), `11` structural replacement path(s)
- `App/Utilities/Managers/System/IteratorManager.php`: `9` native-call occurrence(s), `0` low-friction replacement path(s), `9` structural replacement path(s)
- `App/Abstracts/Database/Model.php`: `8` native-call occurrence(s), `0` low-friction replacement path(s), `9` structural replacement path(s)
- `App/Drivers/Cryptography/OpenSSLCrypto.php`: `8` native-call occurrence(s), `0` low-friction replacement path(s), `9` structural replacement path(s)
- `App/Abstracts/Presentation/View.php`: `7` native-call occurrence(s), `0` low-friction replacement path(s), `8` structural replacement path(s)
- `App/Utilities/Managers/System/ReflectionManager.php`: `7` native-call occurrence(s), `0` low-friction replacement path(s), `7` structural replacement path(s)
- `App/Modules/WebModule/Presenters/PagePresenter.php`: `6` native-call occurrence(s), `0` low-friction replacement path(s), `7` structural replacement path(s)
- `App/Drivers/Caching/DatabaseCache.php`: `4` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Drivers/Caching/FileCache.php`: `4` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Drivers/Caching/MemCache.php`: `4` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Drivers/Caching/RedisCache.php`: `4` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Utilities/Managers/Data/CryptoManager.php`: `4` native-call occurrence(s), `0` low-friction replacement path(s), `5` structural replacement path(s)
- `App/Abstracts/Data/Finder.php`: `3` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Abstracts/Http/Controller.php`: `3` native-call occurrence(s), `0` low-friction replacement path(s), `3` structural replacement path(s)
- `App/Core/Bootstrap.php`: `3` native-call occurrence(s), `0` low-friction replacement path(s), `3` structural replacement path(s)
- `App/Providers/CacheProvider.php`: `3` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Providers/ModuleProvider.php`: `3` native-call occurrence(s), `0` low-friction replacement path(s), `3` structural replacement path(s)
- `App/Modules/WebModule/Controllers/HomeController.php`: `2` native-call occurrence(s), `0` low-friction replacement path(s), `2` structural replacement path(s)
- `App/Modules/WebModule/Responses/WebResponse.php`: `2` native-call occurrence(s), `0` low-friction replacement path(s), `3` structural replacement path(s)
- `App/Utilities/Handlers/CryptoHandler.php`: `2` native-call occurrence(s), `0` low-friction replacement path(s), `4` structural replacement path(s)
- `App/Utilities/Handlers/SQLHandler.php`: `2` native-call occurrence(s), `0` low-friction replacement path(s), `3` structural replacement path(s)
- `App/Modules/WebModule/Services/PageService.php`: `1` native-call occurrence(s), `0` low-friction replacement path(s), `2` structural replacement path(s)
- `App/Modules/WebModule/Views/WebView.php`: `1` native-call occurrence(s), `0` low-friction replacement path(s), `1` structural replacement path(s)
- `App/Utilities/Handlers/DataStructureHandler.php`: `1` native-call occurrence(s), `0` low-friction replacement path(s), `1` structural replacement path(s)
- `App/Utilities/Managers/System/ErrorManager.php`: `1` native-call occurrence(s), `0` low-friction replacement path(s), `2` structural replacement path(s)
- `App/Utilities/Query/SchemaQuery.php`: `1` native-call occurrence(s), `0` low-friction replacement path(s), `1` structural replacement path(s)

## Per-Class Findings

### `App/Abstracts/Data/Finder.php`

- Current traits: `App\Utilities\Traits\ApplicationPathTrait`, `App\Utilities\Traits\ArrayTrait`, `App\Utilities\Traits\ErrorTrait`, `App\Utilities\Traits\LoopTrait`
- Line `318` uses native `is_array` in ``is_array($criteria['pattern'])``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `404` uses native `strtolower` in ``$direction = strtolower((string) ($sortCriteria['direction'] ?? 'asc'));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)
- Line `504` uses native `is_array` in ``: (is_array($criteria['pattern'])``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)

### `App/Abstracts/Database/Model.php`

- Current traits: none
- Line `79` uses native `trim` in ``$key = trim($key);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)
- Line `95` uses native `array_key_exists` in ``return array_key_exists($key, $this->attributes);``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::keyExists()` (`Framework array access already exposes a dedicated key check.`)
- Line `144` uses native `array_key_exists` in ``if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::keyExists()` (`Framework array access already exposes a dedicated key check.`)
- Line `158` uses native `array_key_exists` in ``return array_key_exists($attribute, $this->getDirty());``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::keyExists()` (`Framework array access already exposes a dedicated key check.`)
- Line `228` uses native `in_array` in ``return in_array($attribute, $this->fillable, true);``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInArray()` (`Prefer the shared inclusion check helper.`)
- Line `235` uses native `in_array` in ``return !in_array($attribute, $this->guarded, true);``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInArray()` (`Prefer the shared inclusion check helper.`)
- Line `242` uses native `preg_replace` in ``$snakeCase = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `242` uses native `strtolower` in ``$snakeCase = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)

### `App/Abstracts/Http/Controller.php`

- Current traits: `App\Utilities\Traits\ErrorTrait`
- Line `108` uses native `is_array` in ``if (is_array($result)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `174` uses native `method_exists` in ``if (!method_exists($this->presenter, $method)) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::methodExists()` (`A framework existence check wrapper already exists.`)
- Line `240` uses native `method_exists` in ``if (!method_exists($this->view, $method)) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::methodExists()` (`A framework existence check wrapper already exists.`)

### `App/Abstracts/Presentation/View.php`

- Current traits: `App\Utilities\Traits\ErrorTrait`
- Line `100` uses native `strtolower` in ``return match (strtolower($type)) {``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)
- Line `111` uses native `array_replace` in ``$this->globals = array_replace($this->globals, $variables);``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::replace()` (`Prefer the framework array replacement helper.`)
- Line `133` uses native `is_string` in ``return is_string($cached) ? $cached : null;``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)
- Line `147` uses native `array_key_first` in ``array_key_first($this->dirs->find(['name' => $dirName])) ?: null,``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::keyFirst()` (`Use the shared array helper for first-key access.`)
- Line `165` uses native `array_key_first` in ``array_key_first($this->dirs->find(['name' => $subDir], $basePath)) ?: null,``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::keyFirst()` (`Use the shared array helper for first-key access.`)
- Line `300` uses native `array_replace` in ``$variables = array_replace($this->globals, $data);``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::replace()` (`Prefer the framework array replacement helper.`)
- Line `319` uses native `is_string` in ``return is_string($result ?? null) ? $result : '';``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)

### `App/Core/Bootstrap.php`

- Current traits: none
- Line `62` uses native `function_exists` in ``if ($this->isHttpContext() && function_exists('header_remove')) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::functionExists()` (`A framework existence check wrapper already exists.`)
- Line `97` uses native `str_replace` in ``$normalizedDirectory = trim(str_replace('\\', '/', $scriptDirectory), '/');``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `97` uses native `trim` in ``$normalizedDirectory = trim(str_replace('\\', '/', $scriptDirectory), '/');``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Drivers/Caching/DatabaseCache.php`

- Current traits: none
- Line `26` uses native `base64_encode` in ``'data' => base64_encode(``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `38` uses native `json_encode` in ``[$key, json_encode($cacheData), $cacheData['timestamp'], $ttl]``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `63` uses native `json_decode` in ``$cacheData = json_decode($result['cache_data'], true);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::fromJson()` (`Prefer the shared JSON decoding helper.`)
- Line `67` uses native `base64_decode` in ``base64_decode((string) ($cacheData['data'] ?? ''), true) ?: ''``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)

### `App/Drivers/Caching/FileCache.php`

- Current traits: none
- Line `15` uses native `json_encode` in ``$cacheData = json_encode([``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `18` uses native `base64_encode` in ``'data' => base64_encode(``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `47` uses native `json_decode` in ``$cacheData = json_decode($this->fileManager->readContents($cachePath), true);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::fromJson()` (`Prefer the shared JSON decoding helper.`)
- Line `62` uses native `base64_decode` in ``base64_decode((string) ($cacheData['data'] ?? ''), true) ?: ''``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)

### `App/Drivers/Caching/MemCache.php`

- Current traits: none
- Line `23` uses native `json_encode` in ``$result = $this->memcached->set($key, json_encode([``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `26` uses native `base64_encode` in ``'data' => base64_encode(``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `53` uses native `json_decode` in ``$cacheData = json_decode($result, true);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::fromJson()` (`Prefer the shared JSON decoding helper.`)
- Line `57` uses native `base64_decode` in ``base64_decode((string) ($cacheData['data'] ?? ''), true) ?: ''``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)

### `App/Drivers/Caching/RedisCache.php`

- Current traits: none
- Line `25` uses native `json_encode` in ``json_encode([``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `28` uses native `base64_encode` in ``'data' => base64_encode(``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `60` uses native `json_decode` in ``$cacheData = json_decode($result, true);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::fromJson()` (`Prefer the shared JSON decoding helper.`)
- Line `64` uses native `base64_decode` in ``base64_decode((string) ($cacheData['data'] ?? ''), true) ?: ''``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)

### `App/Drivers/Cryptography/OpenSSLCrypto.php`

- Current traits: none
- Line `406` uses native `base64_decode` in ``base64_decode(``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `407` uses native `preg_replace` in ``str_replace(["\n", "\r", " "], '', preg_replace('/-----BEGIN (.+?)-----|-----END (.+?)-----/', '', $pem))``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `407` uses native `str_replace` in ``str_replace(["\n", "\r", " "], '', preg_replace('/-----BEGIN (.+?)-----|-----END (.+?)-----/', '', $pem))``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `411` uses native `base64_encode` in ``"-----BEGIN {$label}-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END {$label}-----\n",``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `591` uses native `base64_encode` in ``base64_encode($data),``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `594` uses native `base64_decode` in ``base64_decode($data, true)``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `687` uses native `preg_replace` in ``$normalized = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $cipher));``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `687` uses native `strtolower` in ``$normalized = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $cipher));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)

### `App/Modules/WebModule/Controllers/HomeController.php`

- Current traits: none
- Line `59` uses native `is_array` in ``if (!is_array($result)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `64` uses native `is_int` in ``$status = isset($payload['status']) && is_int($payload['status'])``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)

### `App/Modules/WebModule/Presenters/PagePresenter.php`

- Current traits: none
- Line `16` uses native `is_array` in ``$page = isset($data['page']) && is_array($data['page']) ? $data['page'] : [];``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `17` uses native `is_int` in ``$status = isset($data['status']) && is_int($data['status']) ? $data['status'] : 200;``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `27` uses native `is_array` in ``'callToAction' => is_array($page['callToAction'] ?? null)``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `35` uses native `is_int` in ``$status = isset($data['status']) && is_int($data['status']) ? $data['status'] : 200;``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `39` uses native `str_replace` in ``'pageClass' => 'page-' . str_replace('_', '-', strtolower($slug)),``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `39` uses native `strtolower` in ``'pageClass' => 'page-' . str_replace('_', '-', strtolower($slug)),``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)

### `App/Modules/WebModule/Requests/WebRequest.php`

- Current traits: none
- Line `36` uses native `is_array` in ``is_array($_FILES ?? null) ? $_FILES : [],``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `52` uses native `is_array` in ``$query = is_array($_GET ?? null) ? $_GET : [];``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `53` uses native `is_array` in ``$body = is_array($_POST ?? null) ? $_POST : [];``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `55` uses native `array_replace` in ``return array_replace($query, $body);``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::replace()` (`Prefer the framework array replacement helper.`)
- Line `63` uses native `function_exists` in ``if (function_exists('getallheaders')) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::functionExists()` (`A framework existence check wrapper already exists.`)
- Line `66` uses native `is_array` in ``if (is_array($headers)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `67` uses native `array_map` in ``return array_map(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::map()` (`Use the existing array mapping helper for consistency.`)
- Line `68` uses native `is_scalar` in ``static fn(mixed $value): string => is_scalar($value) ? trim((string) $value) : '',``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isScalar()` (`Prefer the shared type helper for scalar checks.`)
- Line `68` uses native `trim` in ``static fn(mixed $value): string => is_scalar($value) ? trim((string) $value) : '',``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)
- Line `77` uses native `is_scalar` in ``if (!is_string($key) || !is_scalar($value)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isScalar()` (`Prefer the shared type helper for scalar checks.`)
- Line `77` uses native `is_string` in ``if (!is_string($key) || !is_scalar($value)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)
- Line `81` uses native `str_starts_with` in ``if (str_starts_with($key, 'HTTP_')) {``
  - `available-via-trait` -> `App\Utilities\Traits\CheckerTrait::startsWith()` (`Prefer the shared string check helper.`)
- Line `82` uses native `str_replace` in ``$normalized = str_replace('_', '-', substr($key, 5));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `82` uses native `substr` in ``$normalized = str_replace('_', '-', substr($key, 5));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::substring()` (`Prefer the shared string slicing helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::getSubstringOfString()` (`Use the encoding-aware substring helper when multibyte support matters.`)
- Line `83` uses native `trim` in ``$headers[$normalized] = trim((string) $value);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)
- Line `87` uses native `in_array` in ``if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInArray()` (`Prefer the shared inclusion check helper.`)
- Line `88` uses native `str_replace` in ``$normalized = str_replace('_', '-', $key);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `89` uses native `trim` in ``$headers[$normalized] = trim((string) $value);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Modules/WebModule/Responses/WebResponse.php`

- Current traits: none
- Line `34` uses native `array_map` in ``array_map(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::map()` (`Use the existing array mapping helper for consistency.`)
- Line `36` uses native `strtolower` in ``explode('-', strtolower($header))``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)

### `App/Modules/WebModule/Services/PageService.php`

- Current traits: none
- Line `53` uses native `strtolower` in ``if (strtolower((string) $this->config->get('webmodule', 'CONTENT_SOURCE', 'memory')) !== 'database') {``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)

### `App/Modules/WebModule/Views/WebView.php`

- Current traits: none
- Line `46` uses native `array_replace` in ``return parent::renderLayout($this->defaultLayout, array_replace($data, [``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::replace()` (`Prefer the framework array replacement helper.`)

### `App/Providers/CacheProvider.php`

- Current traits: none
- Line `82` uses native `preg_replace` in ``$driver = strtolower(trim((string) preg_replace('/\s+#.*$/', '', (string) ($cacheSettings['DRIVER'] ?? ''))));``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `82` uses native `strtolower` in ``$driver = strtolower(trim((string) preg_replace('/\s+#.*$/', '', (string) ($cacheSettings['DRIVER'] ?? ''))));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)
- Line `82` uses native `trim` in ``$driver = strtolower(trim((string) preg_replace('/\s+#.*$/', '', (string) ($cacheSettings['DRIVER'] ?? ''))));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Providers/ModuleProvider.php`

- Current traits: none
- Line `35` uses native `is_array` in ``if (!is_array($class) || !isset($class['class'], $class['shortName'])) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `44` uses native `is_array` in ``if (!is_array($class) || !isset($class['class'], $class['shortName'])) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `109` uses native `preg_match` in ``if (preg_match('/App\\\\Modules\\\\([^\\\\]+)/', $fqcn, $matches) === 1) {``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::match()` (`Use the shared regex helper when regex behavior should align across the framework.`)

### `App/Utilities/Handlers/CryptoHandler.php`

- Current traits: none
- Line `46` uses native `substr` in ``$nonce = substr($ciphertext, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::substring()` (`Prefer the shared string slicing helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::getSubstringOfString()` (`Use the encoding-aware substring helper when multibyte support matters.`)
- Line `47` uses native `substr` in ``$ciphertext = substr($ciphertext, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::substring()` (`Prefer the shared string slicing helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::getSubstringOfString()` (`Use the encoding-aware substring helper when multibyte support matters.`)

### `App/Utilities/Handlers/DataHandler.php`

- Current traits: none
- Line `45` uses native `json_encode` in ``return json_encode($value, $options, $depth);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `59` uses native `json_decode` in ``return json_decode($json, $assoc, $depth, $options);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::fromJson()` (`Prefer the shared JSON decoding helper.`)
- Line `90` uses native `json_encode` in ``return json_encode($object);``
  - `available-via-trait` -> `App\Utilities\Traits\ConversionTrait::toJson()` (`Prefer the shared JSON encoding helper.`)
- Line `238` uses native `is_string` in ``if (!is_string($value) || trim($value) === '') {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)
- Line `238` uses native `trim` in ``if (!is_string($value) || trim($value) === '') {``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)
- Line `350` uses native `base64_encode` in ``return base64_encode($data);``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64EncodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `361` uses native `base64_decode` in ``return base64_decode($data);``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::base64DecodeString()` (`Use the encoding helper to keep binary/text conversions centralized.`)
- Line `374` uses native `urlencode` in ``return urlencode($data);``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::encodeStringForUrl()` (`Prefer the shared encoding helper for URL encoding.`)
- Line `385` uses native `urldecode` in ``return urldecode($data);``
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::decodeStringFromUrl()` (`Prefer the shared encoding helper for URL decoding.`)
- Line `592` uses native `is_array` in ``if (is_array($value)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `594` uses native `is_string` in ``$nodeName = is_string($key) && $key !== ''``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)
- Line `610` uses native `is_object` in ``if (is_object($value)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isObject()` (`Prefer the shared type helper for object checks.`)
- Line `630` uses native `preg_replace` in ``$normalized = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', trim($name)) ?? '';``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `630` uses native `trim` in ``$normalized = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', trim($name)) ?? '';``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Utilities/Handlers/DataStructureHandler.php`

- Current traits: none
- Line `197` uses native `is_array` in ``if (is_array($dataStructure)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)

### `App/Utilities/Handlers/SQLHandler.php`

- Current traits: none
- Line `187` uses native `strtolower` in ``$normalized = strtolower(trim((string) $type));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)
- Line `187` uses native `trim` in ``$normalized = strtolower(trim((string) $type));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Utilities/Managers/Data/CryptoManager.php`

- Current traits: none
- Line `39` uses native `is_int` in ``if ($parameterCount > 1 && count($args) === 1 && is_int($args[0])) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `63` uses native `preg_replace` in ``$driver = strtolower(trim((string) preg_replace(``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::replace()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `63` uses native `strtolower` in ``$driver = strtolower(trim((string) preg_replace(``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toLower()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToLower()` (`Use the encoding-aware lowercase helper when multibyte support matters.`)
- Line `63` uses native `trim` in ``$driver = strtolower(trim((string) preg_replace(``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

### `App/Utilities/Managers/System/ErrorManager.php`

- Current traits: `App\Utilities\Traits\ArrayTrait`, `App\Utilities\Traits\ExistenceCheckerTrait`, `App\Utilities\Traits\TypeCheckerTrait`
- Line `193` uses native `strtoupper` in ``? sprintf("[%s][%s] %s in %s on line %d", strtoupper($context), strtoupper($key), $message, $file, $line)``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::toUpper()` (`Prefer the shared string case helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::convertStringToUpper()` (`Use the encoding-aware uppercase helper when multibyte support matters.`)

### `App/Utilities/Managers/System/FileManager.php`

- Current traits: none
- Line `189` uses native `str_replace` in ``$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::replace()` (`Prefer the shared string replacement helper where string semantics are intended.`)
- Line `189` uses native `trim` in ``$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path));``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)
- Line `197` uses native `preg_match` in ``if (preg_match('/^[A-Za-z]:' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $path) === 1) {``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::match()` (`Use the shared regex helper when regex behavior should align across the framework.`)
- Line `197` uses native `preg_quote` in ``if (preg_match('/^[A-Za-z]:' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $path) === 1) {``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::quote()` (`Use the shared regex helper for quoting patterns.`)
- Line `198` uses native `substr` in ``$prefix = substr($path, 0, 2);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::substring()` (`Prefer the shared string slicing helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::getSubstringOfString()` (`Use the encoding-aware substring helper when multibyte support matters.`)
- Line `199` uses native `substr` in ``$path = substr($path, 2);``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::substring()` (`Prefer the shared string slicing helper.`)
  - `available-via-trait` -> `App\Utilities\Traits\EncodingTrait::getSubstringOfString()` (`Use the encoding-aware substring helper when multibyte support matters.`)
- Line `200` uses native `str_starts_with` in ``} elseif (str_starts_with($path, DIRECTORY_SEPARATOR)) {``
  - `available-via-trait` -> `App\Utilities\Traits\CheckerTrait::startsWith()` (`Prefer the shared string check helper.`)
- Line `207` uses native `preg_split` in ``foreach (preg_split('#[\\\\/]#', $path) ?: [] as $segment) {``
  - `available-via-trait` -> `App\Utilities\Traits\Patterns\PatternTrait::split()` (`Use the shared regex split helper when regex behavior should align across the framework.`)
- Line `213` uses native `array_pop` in ``array_pop($segments);``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::pop()` (`Prefer the shared array pop helper.`)

### `App/Utilities/Managers/System/IteratorManager.php`

- Current traits: `App\Utilities\Traits\Iterator\IteratorTrait`, `App\Utilities\Traits\Iterator\RecursiveIteratorTrait`
- Line `105` uses native `is_int` in ``if (is_int($overrideGroup)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `110` uses native `is_array` in ``if (!is_array($overrideGroup)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isArray()` (`Prefer the shared type helper for array checks.`)
- Line `118` uses native `is_int` in ``if (is_int($key) && is_int($value)) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `133` uses native `is_int` in ``if (isset($overrides['maxDepth']) && is_int($overrides['maxDepth'])) {``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isInt()` (`Prefer the shared type helper for integer checks.`)
- Line `149` uses native `array_reduce` in ``return array_reduce(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::reduce()` (`Prefer the shared array reduction helper.`)
- Line `150` uses native `array_values` in ``[...array_values($defaultFlags), ...array_values($overrideFlags)],``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::getValues()` (`Prefer the shared array values helper.`)
- Line `182` uses native `method_exists` in ``if (method_exists($this, $iteratorName)) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::methodExists()` (`A framework existence check wrapper already exists.`)
- Line `185` uses native `array_merge` in ``: $this->{$iteratorName}(...array_merge($args, [$settings]));``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::merge()` (`Prefer the shared array merge helper.`)
- Line `221` uses native `method_exists` in ``if (method_exists($this->iterator, 'previous')) {``
  - `available-via-trait` -> `App\Utilities\Traits\ExistenceCheckerTrait::methodExists()` (`A framework existence check wrapper already exists.`)

### `App/Utilities/Managers/System/ReflectionManager.php`

- Current traits: `App\Utilities\Traits\Reflection\ReflectionAttributeTrait`, `App\Utilities\Traits\Reflection\ReflectionClassTrait`, `App\Utilities\Traits\Reflection\ReflectionConstantTrait`, `App\Utilities\Traits\Reflection\ReflectionEnumTrait`, `App\Utilities\Traits\Reflection\ReflectionExtensionTrait`, `App\Utilities\Traits\Reflection\ReflectionFunctionTrait`, `App\Utilities\Traits\Reflection\ReflectionGeneratorTrait`, `App\Utilities\Traits\Reflection\ReflectionMethodTrait`, `App\Utilities\Traits\Reflection\ReflectionParameterTrait`, `App\Utilities\Traits\Reflection\ReflectionPropertyTrait`, `App\Utilities\Traits\Reflection\ReflectionTrait`, `App\Utilities\Traits\Reflection\ReflectionTypeTrait`
- Line `201` uses native `is_string` in ``$this->getClassMethod($this->createClass(is_string($class) ? $class : $class::class), $method),``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)
- Line `202` uses native `is_object` in ``is_object($class) ? $class : null,``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isObject()` (`Prefer the shared type helper for object checks.`)
- Line `215` uses native `array_reduce` in ``return array_reduce(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::reduce()` (`Prefer the shared array reduction helper.`)
- Line `230` uses native `array_map` in ``return array_map(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::map()` (`Use the existing array mapping helper for consistency.`)
- Line `252` uses native `array_map` in ``return array_map(``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::map()` (`Use the existing array mapping helper for consistency.`)
- Line `272` uses native `array_keys` in ``return array_keys($this->getClassTraits($this->createClass($class)));``
  - `available-via-trait` -> `App\Utilities\Traits\ArrayTrait::getKeys()` (`A framework-level key extraction helper already exists.`)
- Line `296` uses native `is_string` in ``$reflectionClass = $this->createClass(is_string($class) ? $class : $class::class);``
  - `available-via-trait` -> `App\Utilities\Traits\TypeCheckerTrait::isString()` (`Prefer the shared type helper for string checks.`)

### `App/Utilities/Query/SchemaQuery.php`

- Current traits: `App\Utilities\Traits\Query\SchemaQueryTrait`
- Line `39` uses native `trim` in ``'definition' => $definition ? trim($definition) : null,``
  - `available-via-trait` -> `App\Utilities\Traits\ManipulationTrait::trim()` (`Prefer the shared string-trimming helper.`)

