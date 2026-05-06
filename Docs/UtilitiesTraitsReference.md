# Utilities Traits Reference

This document is generated directly from the current PHP source under `App/Utilities/Traits`.
It is intended to complement `Docs/UtilitiesTraitsOverview.md` with full per-trait coverage.

## Snapshot

- Trait files: `51`
- Total properties declared in traits: `9`
- Total methods declared in traits: `825`
- Wrapper traits: `8`
- Traits with `__construct()`: `0`
- Traits with no current `App/` consumer: `9`

## Coverage Notes

- `Current consumers` only includes concrete usage inside `App/` outside the traits directory.
- `Imports` lists file-level imports declared before the trait.
- `Composed traits` lists traits mixed into the trait body itself.
- `Properties` and `Methods` include line numbers to make navigation easier.

## Category Index

- `Core Traits`: `ApplicationPathTrait`, `ArrayTrait`, `CheckerTrait`, `ConversionTrait`, `DateTimeTrait`, `DirectoryCriteriaTrait`, `DirectorySortTrait`, `EncodingTrait`, `ErrorTrait`, `ExistenceCheckerTrait`, `FileCriteriaTrait`, `FileSortTrait`, `HashingTrait`, `LocaleTrait`, `LocaleUtilityTrait`, `LoopTrait`, `ManipulationTrait`, `MetricsTrait`, `MoneyFormattingTrait`, `RetrieverTrait`, `TypeCheckerTrait`
- `Criteria`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `Filters`: `FiltrationTrait`, `SanitationFilterTrait`, `SanitationTrait`, `ValidationFilterTrait`, `ValidationTrait`
- `Iterator`: `IteratorTrait`, `RecursiveIteratorTrait`
- `Patterns`: `PatternTrait`, `SanitationPatternTrait`, `ValidationPatternTrait`
- `Query`: `DataQueryTrait`, `SchemaQueryTrait`
- `Reflection`: `ReflectionAttributeTrait`, `ReflectionClassTrait`, `ReflectionConstantTrait`, `ReflectionEnumTrait`, `ReflectionExtensionTrait`, `ReflectionFunctionTrait`, `ReflectionGeneratorTrait`, `ReflectionMethodTrait`, `ReflectionParameterTrait`, `ReflectionPropertyTrait`, `ReflectionTrait`, `ReflectionTypeTrait`
- `Rules`: `RuleTrait`, `RulesTrait`
- `Sort`: `DirectorySortTrait`, `FileSortTrait`

## Core Traits

### `ApplicationPathTrait`

- Path: `App/Utilities/Traits/ApplicationPathTrait.php`
- FQCN: `App\Utilities\Traits\ApplicationPathTrait`
- Type: `method provider`
- Summary: `Resolves framework base and storage paths in a reusable way.`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Presentation/View.php`, `App/Console/Commands/ModuleMakeCommand.php`, `App/Console/Commands/ReleaseCheckCommand.php`, `App/Installer/InstallerWizard.php`, `App/Utilities/Managers/Async/QueueManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Support/ArchitectureAlignmentManager.php`, `App/Utilities/Managers/Support/FrameworkDoctor.php`, `App/Utilities/Managers/Support/FrameworkLayerManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `2` total (`0` public, `2` protected, `0` private)
  - `protected function frameworkBasePath(): string` at `App/Utilities/Traits/ApplicationPathTrait.php:16`
  - `protected function frameworkStoragePath(string $path = ''): string` at `App/Utilities/Traits/ApplicationPathTrait.php:27`

### `ArrayTrait`

- Path: `App/Utilities/Traits/ArrayTrait.php`
- FQCN: `App\Utilities\Traits\ArrayTrait`
- Type: `method provider`
- Summary: `Provides utility methods for common array operations with flexible parameter handling.`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Data/SchemaProcessor.php`, `App/Abstracts/Database/Model.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Database/Repository.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Abstracts/Presentation/Resource.php`, `App/Abstracts/Presentation/View.php`, `App/Abstracts/Support/Mailable.php`, `App/Abstracts/Support/Notification.php`, `App/Abstracts/Support/PaymentDriver.php`, `App/Console/ConsoleKernel.php`, `App/Core/Config.php`, `App/Core/Container.php`, `App/Core/Database.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/Schema/Blueprint.php`, `App/Core/SeedRunner.php`, `App/Core/Session.php`, `App/Drivers/Passkeys/TestingPasskeyDriver.php`, `App/Drivers/Passkeys/WebAuthnPasskeyDriver.php`, `App/Drivers/Payments/TestingPaymentDriver.php`, `App/Drivers/Queue/DatabaseQueueDriver.php`, `App/Drivers/Queue/SyncQueueDriver.php`, `App/Drivers/Session/FileSessionDriver.php`, `App/Modules/UserModule/Services/UserAuthService.php`, `App/Modules/UserModule/Services/UserPasskeyService.php`, `App/Utilities/Handlers/DataStructureHandler.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Managers/Async/EventDispatcher.php`, `App/Utilities/Managers/Async/QueueManager.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Presentation/ThemeManager.php`, `App/Utilities/Managers/Security/DatabaseUserProvider.php`, `App/Utilities/Managers/Security/Gate.php`, `App/Utilities/Managers/Security/HttpSecurityManager.php`, `App/Utilities/Managers/Security/PermissionRegistry.php`, `App/Utilities/Managers/Security/PolicyResolver.php`, `App/Utilities/Managers/Support/ArchitectureAlignmentManager.php`, `App/Utilities/Managers/Support/FrameworkDoctor.php`, `App/Utilities/Managers/Support/FrameworkLayerManager.php`, `App/Utilities/Managers/Support/MailManager.php`, `App/Utilities/Managers/Support/NotificationManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`, `App/Utilities/Managers/System/ErrorManager.php`, `App/Utilities/Managers/System/FileManager.php`, `App/Utilities/Managers/System/IteratorManager.php`, `App/Utilities/Managers/System/ReflectionManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `78` total (`78` public, `0` protected, `0` private)
  - `public function changeKeyCase(array $array, int $case = CASE_LOWER): array` at `App/Utilities/Traits/ArrayTrait.php:21`
  - `public function chunk(array $array, int $size, bool $preserveKeys = false): array` at `App/Utilities/Traits/ArrayTrait.php:34`
  - `public function column(array $array, int|string|null $columnKey, int|string|null $indexKey = null): array` at `App/Utilities/Traits/ArrayTrait.php:47`
  - `public function combine(array $keys, array $values): array` at `App/Utilities/Traits/ArrayTrait.php:59`
  - `public function diff(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:70`
  - `public function diffAssoc(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:81`
  - `public function diffKey(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:92`
  - `public function fill(int $startIndex, int $count, mixed $value): array` at `App/Utilities/Traits/ArrayTrait.php:105`
  - `public function fillKeys(array $keys, mixed $value): array` at `App/Utilities/Traits/ArrayTrait.php:117`
  - `public function filter(array $array, callable|null $callback = null, int $mode = 0): array` at `App/Utilities/Traits/ArrayTrait.php:129`
  - `public function flip(array $array): array` at `App/Utilities/Traits/ArrayTrait.php:142`
  - `public function intersect(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:153`
  - `public function intersectAssoc(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:164`
  - `public function intersectKey(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:175`
  - `public function map(callable $callback, array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:187`
  - `public function create(int|string $start, int|string $end, int $step = 1): array` at `App/Utilities/Traits/ArrayTrait.php:200`
  - `public function assign(array $variables, array $values): array` at `App/Utilities/Traits/ArrayTrait.php:212`
  - `public function all(array $array, callable $callback): bool` at `App/Utilities/Traits/ArrayTrait.php:224`
  - `public function any(array $array, callable $callback): bool` at `App/Utilities/Traits/ArrayTrait.php:236`
  - `public function getCurrentValue(array $array): mixed` at `App/Utilities/Traits/ArrayTrait.php:247`
  - `public function getLastValue(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:258`
  - `public function getCurrentKey(array $array): int|string|null` at `App/Utilities/Traits/ArrayTrait.php:269`
  - `public function getNextValue(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:280`
  - `public function getPreviousValue(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:291`
  - `public function getFirstValue(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:302`
  - `public function find(array $array, callable $callback): mixed` at `App/Utilities/Traits/ArrayTrait.php:314`
  - `public function findKey(array $array, callable $callback): int|string|null` at `App/Utilities/Traits/ArrayTrait.php:326`
  - `public function keyFirst(array $array): mixed` at `App/Utilities/Traits/ArrayTrait.php:337`
  - `public function keyLast(array $array): mixed` at `App/Utilities/Traits/ArrayTrait.php:348`
  - `public function extractVariables(array &$array, int $flags = EXTR_OVERWRITE): int` at `App/Utilities/Traits/ArrayTrait.php:360`
  - `public function diffUKey(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:373`
  - `public function intersectUKey(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:386`
  - `public function uDiffUAssoc(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:397`
  - `public function uIntersectUAssoc(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:408`
  - `public function keyExists(array $array, int|string $key): bool` at `App/Utilities/Traits/ArrayTrait.php:420`
  - `public function getKeys(array $data): array` at `App/Utilities/Traits/ArrayTrait.php:431`
  - `public function getValues(array $data): array` at `App/Utilities/Traits/ArrayTrait.php:442`
  - `public function search(array $data, mixed $value): mixed` at `App/Utilities/Traits/ArrayTrait.php:454`
  - `public function flatten(array $data): array` at `App/Utilities/Traits/ArrayTrait.php:465`
  - `public function countValues(array $array): array` at `App/Utilities/Traits/ArrayTrait.php:476`
  - `public function uDiff(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:489`
  - `public function uDiffAssoc(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:502`
  - `public function uIntersectAssoc(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:515`
  - `public function merge(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:526`
  - `public function mergeRecursive(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:537`
  - `public function multisort(array &$array, int $sortFlags = SORT_REGULAR): bool` at `App/Utilities/Traits/ArrayTrait.php:549`
  - `public function padArray(array $array, int $size, mixed $value): array` at `App/Utilities/Traits/ArrayTrait.php:566`
  - `public function replaceElements(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:576`
  - `public function replaceRecursive(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:587`
  - `public function walk(array &$array, callable $callback, mixed $userdata = null): bool` at `App/Utilities/Traits/ArrayTrait.php:600`
  - `public function diffUAssoc(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:613`
  - `public function intersectUAssoc(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:626`
  - `public function uIntersect(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:639`
  - `public function sortNaturalCaseInsensitive(array &$array): bool` at `App/Utilities/Traits/ArrayTrait.php:650`
  - `public function sortNatural(array &$array): bool` at `App/Utilities/Traits/ArrayTrait.php:661`
  - `public function countElements(array $array, int $mode = COUNT_NORMAL): int` at `App/Utilities/Traits/ArrayTrait.php:673`
  - `public function randomKeys(array $array, int $num = 1): mixed` at `App/Utilities/Traits/ArrayTrait.php:685`
  - `public function reverseArray(array $array, bool $preserveKeys = false): array` at `App/Utilities/Traits/ArrayTrait.php:697`
  - `public function pop(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:708`
  - `public function push(array &$array, mixed ...$values): int` at `App/Utilities/Traits/ArrayTrait.php:720`
  - `public function shift(array &$array): mixed` at `App/Utilities/Traits/ArrayTrait.php:731`
  - `public function unshift(array &$array, mixed ...$values): int` at `App/Utilities/Traits/ArrayTrait.php:743`
  - `public function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array` at `App/Utilities/Traits/ArrayTrait.php:757`
  - `public function splice(array &$array, int $offset, ?int $length = null, mixed $replacement = []): array` at `App/Utilities/Traits/ArrayTrait.php:771`
  - `public function product(array $array): int|float` at `App/Utilities/Traits/ArrayTrait.php:782`
  - `public function sum(array $array): int|float` at `App/Utilities/Traits/ArrayTrait.php:793`
  - `public function unique(array $array, int $flags = SORT_STRING): array` at `App/Utilities/Traits/ArrayTrait.php:805`
  - `public function walkRecursive(array &$array, callable $callback): bool` at `App/Utilities/Traits/ArrayTrait.php:817`
  - `public function isList(array $array): bool` at `App/Utilities/Traits/ArrayTrait.php:828`
  - `public function arraykeyExists(int|string $key, array $array): bool` at `App/Utilities/Traits/ArrayTrait.php:840`
  - `public function differenceByKeys(array $array1, array $array2, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:853`
  - `public function filterNonEmpty(array $array): array` at `App/Utilities/Traits/ArrayTrait.php:864`
  - `public function diffKeyRecursive(array $array1, array $array2): array` at `App/Utilities/Traits/ArrayTrait.php:876`
  - `public function reduce(array $array, callable $callback, mixed $initial = null): mixed` at `App/Utilities/Traits/ArrayTrait.php:895`
  - `public function shuffleArray(array $array): array` at `App/Utilities/Traits/ArrayTrait.php:906`
  - `public function sortRecursive(array &$array): void` at `App/Utilities/Traits/ArrayTrait.php:919`
  - `public function mergeUnique(array ...$arrays): array` at `App/Utilities/Traits/ArrayTrait.php:935`
  - `public function partition(array $array, callable $callback): array` at `App/Utilities/Traits/ArrayTrait.php:947`

### `CheckerTrait`

- Path: `App/Utilities/Traits/CheckerTrait.php`
- FQCN: `App\Utilities\Traits\CheckerTrait`
- Type: `method provider`
- Summary: `Provides utility functions for checking various properties of strings.`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Http/Request.php`, `App/Console/ConsoleKernel.php`, `App/Core/App.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/Session.php`, `App/Drivers/Passkeys/TestingPasskeyDriver.php`, `App/Drivers/Passkeys/WebAuthnPasskeyDriver.php`, `App/Drivers/Queue/SyncQueueDriver.php`, `App/Drivers/Session/EncryptedSessionDriver.php`, `App/Modules/UserModule/Services/UserAuthService.php`, `App/Modules/UserModule/Services/UserPasskeyService.php`, `App/Utilities/Managers/Async/EventDispatcher.php`, `App/Utilities/Managers/Async/QueueManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Security/DatabaseUserProvider.php`, `App/Utilities/Managers/Security/Gate.php`, `App/Utilities/Managers/Security/PolicyResolver.php`, `App/Utilities/Managers/Security/SessionGuard.php`, `App/Utilities/Managers/Support/ArchitectureAlignmentManager.php`, `App/Utilities/Managers/Support/MailManager.php`, `App/Utilities/Managers/Support/NotificationManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`, `App/Utilities/Managers/System/FileManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `11` total (`11` public, `0` protected, `0` private)
  - `public function isAlphanumeric(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:22`
  - `public function isAlphabetic(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:33`
  - `public function isDigitString(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:44`
  - `public function isLowercase(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:55`
  - `public function isUppercase(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:66`
  - `public function isWhitespace(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:77`
  - `public function contains(string $haystack, string $needle): bool` at `App/Utilities/Traits/CheckerTrait.php:91`
  - `public function startsWith(string $haystack, string $needle): bool` at `App/Utilities/Traits/CheckerTrait.php:103`
  - `public function endsWith(string $haystack, string $needle): bool` at `App/Utilities/Traits/CheckerTrait.php:115`
  - `public function isJson(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:128`
  - `public function isHexadecimal(string $input): bool` at `App/Utilities/Traits/CheckerTrait.php:140`

### `ConversionTrait`

- Path: `App/Utilities/Traits/ConversionTrait.php`
- FQCN: `App\Utilities\Traits\ConversionTrait`
- Type: `method provider`
- Summary: `Provides utility functions for converting data types in PHP.`
- Current consumers: `App/Abstracts/Console/Command.php`, `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/SchemaProcessor.php`, `App/Abstracts/Database/Model.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Database/Repository.php`, `App/Abstracts/Database/Seed.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Http/Service.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Abstracts/Presentation/View.php`, `App/Abstracts/Support/PaymentDriver.php`, `App/Core/App.php`, `App/Core/Router.php`, `App/Drivers/Notifications/DatabaseNotificationChannel.php`, `App/Drivers/Passkeys/WebAuthnPasskeyDriver.php`, `App/Drivers/Queue/DatabaseQueueDriver.php`, `App/Utilities/Handlers/DataHandler.php`, `App/Utilities/Managers/Async/DatabaseFailedJobStore.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`, `App/Utilities/Managers/Support/AuditLogger.php`, `App/Utilities/Managers/Support/MailManager.php`, `App/Utilities/Managers/Support/NotificationManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`
- Methods: `15` total (`15` public, `0` protected, `0` private)
  - `public function toBool(mixed $input): bool` at `App/Utilities/Traits/ConversionTrait.php:22`
  - `public function toFloat(mixed $input): float` at `App/Utilities/Traits/ConversionTrait.php:33`
  - `public function toInt(mixed $input): int` at `App/Utilities/Traits/ConversionTrait.php:44`
  - `public function changeType(mixed &$input, string $type): bool` at `App/Utilities/Traits/ConversionTrait.php:56`
  - `public function toString(mixed $input): string` at `App/Utilities/Traits/ConversionTrait.php:67`
  - `public function toJson(mixed $input, int $flags = 0, int $depth = 512): string` at `App/Utilities/Traits/ConversionTrait.php:82`
  - `public function fromJson(string $json, bool $assoc = true, int $depth = 512, int $flags = 0): mixed` at `App/Utilities/Traits/ConversionTrait.php:96`
  - `public function toDateTime(string $input, string $format = 'Y-m-d H:i:s'): \DateTime|false` at `App/Utilities/Traits/ConversionTrait.php:110`
  - `public function fromDateTime(\DateTime $date, string $format = 'Y-m-d H:i:s'): string` at `App/Utilities/Traits/ConversionTrait.php:122`
  - `public function serializeData(mixed $input): string` at `App/Utilities/Traits/ConversionTrait.php:135`
  - `public function unserializeData(string $input): mixed` at `App/Utilities/Traits/ConversionTrait.php:146`
  - `public function binToHex(string $input): string` at `App/Utilities/Traits/ConversionTrait.php:159`
  - `public function hexToBin(string $input): string` at `App/Utilities/Traits/ConversionTrait.php:170`
  - `public function stringToArray(string $input): array` at `App/Utilities/Traits/ConversionTrait.php:181`
  - `public function arrayToString(array $input): string` at `App/Utilities/Traits/ConversionTrait.php:192`

### `DateTimeTrait`

- Path: `App/Utilities/Traits/DateTimeTrait.php`
- FQCN: `App\Utilities\Traits\DateTimeTrait`
- Type: `method provider`
- Summary: `DateTimeTrait provides utility methods for handling various date and time operations.`
- Current consumers: `App/Utilities/Managers/System/DateTimeManager.php`
- Properties:
  - `public readonly array $traitConstants` at `App/Utilities/Traits/DateTimeTrait.php:19`
- Methods: `20` total (`20` public, `0` protected, `0` private)
  - `public function initializeDateTimeTrait(): void` at `App/Utilities/Traits/DateTimeTrait.php:21`
  - `public function isValidDate(int $month, int $day, int $year): bool` at `App/Utilities/Traits/DateTimeTrait.php:56`
  - `public function formatTimestamp(string $format, ?int $timestamp = null): string` at `App/Utilities/Traits/DateTimeTrait.php:68`
  - `public function getDefaultTimezone(): string` at `App/Utilities/Traits/DateTimeTrait.php:78`
  - `public function setDefaultTimezone(string $timezone): bool` at `App/Utilities/Traits/DateTimeTrait.php:89`
  - `public function parseDate(string $datetime): array` at `App/Utilities/Traits/DateTimeTrait.php:100`
  - `public function parseDateFromFormat(string $format, string $datetime): array` at `App/Utilities/Traits/DateTimeTrait.php:112`
  - `public function getSunInfo(float $latitude, float $longitude, int $timestamp): array` at `App/Utilities/Traits/DateTimeTrait.php:125`
  - `public function getSunrise(float $latitude, float $longitude, int $timestamp, int $returnFormat = self::SUN_RETURN_STRING): string|int|float` at `App/Utilities/Traits/DateTimeTrait.php:139`
  - `public function getSunset(float $latitude, float $longitude, int $timestamp, int $returnFormat = self::SUN_RETURN_STRING): string|int|float` at `App/Utilities/Traits/DateTimeTrait.php:153`
  - `public function getDateInfo(?int $timestamp = null): array` at `App/Utilities/Traits/DateTimeTrait.php:164`
  - `public function getMicroTime(bool $asFloat = false): string|float` at `App/Utilities/Traits/DateTimeTrait.php:175`
  - `public function getCurrentTimestamp(): int` at `App/Utilities/Traits/DateTimeTrait.php:185`
  - `public function parseToTimestamp(string $datetime, ?int $baseTimestamp = null): int|false` at `App/Utilities/Traits/DateTimeTrait.php:197`
  - `public function formatGmtDate(string $format, ?int $timestamp = null): string` at `App/Utilities/Traits/DateTimeTrait.php:209`
  - `public function getLocalTime(bool $asAssociativeArray = true): array|int` at `App/Utilities/Traits/DateTimeTrait.php:220`
  - `public function listTimeZoneAbbreviations(): array` at `App/Utilities/Traits/DateTimeTrait.php:230`
  - `public function listTimeZoneIdentifiers(int $group = DateTimeZone::ALL, ?string $country = null): array` at `App/Utilities/Traits/DateTimeTrait.php:242`
  - `public function getTimeZoneNameFromAbbr(string $abbr, int $offset = 0, int $isDST = 0): string|false` at `App/Utilities/Traits/DateTimeTrait.php:255`
  - `public function getTimeZoneDbVersion(): string` at `App/Utilities/Traits/DateTimeTrait.php:265`

### `DirectoryCriteriaTrait`

- Path: `App/Utilities/Traits/DirectoryCriteriaTrait.php`
- FQCN: `App\Utilities\Traits\DirectoryCriteriaTrait`
- Type: `wrapper trait`
- Composed traits: `Criteria\DirectoryCriteriaTrait`
- Current consumers: `App/Utilities/Finders/DirectoryFinder.php`
- Methods: none declared directly

### `DirectorySortTrait`

- Path: `App/Utilities/Traits/DirectorySortTrait.php`
- FQCN: `App\Utilities\Traits\DirectorySortTrait`
- Type: `wrapper trait`
- Composed traits: `Sort\DirectorySortTrait`
- Current consumers: `App/Utilities/Finders/DirectoryFinder.php`
- Methods: none declared directly

### `EncodingTrait`

- Path: `App/Utilities/Traits/EncodingTrait.php`
- FQCN: `App\Utilities\Traits\EncodingTrait`
- Type: `method provider`
- Summary: `Provides a set of utility functions for encoding, decoding, and string manipulation operations.`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Presentation/View.php`, `App/Core/Router.php`, `App/Core/Session.php`, `App/Drivers/Cryptography/OpenSSLCrypto.php`, `App/Drivers/Session/EncryptedSessionDriver.php`, `App/Modules/UserModule/Services/UserAuthService.php`, `App/Utilities/Handlers/DataHandler.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`
- Methods: `27` total (`27` public, `0` protected, `0` private)
  - `public function addSlashesToString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:22`
  - `public function stripSlashesFromString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:33`
  - `public function base64EncodeString(string $data): string` at `App/Utilities/Traits/EncodingTrait.php:46`
  - `public function base64DecodeString(string $data, bool $strict = false): string|false` at `App/Utilities/Traits/EncodingTrait.php:58`
  - `public function encodeStringForUrl(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:71`
  - `public function decodeStringFromUrl(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:82`
  - `public function encodeStringForRawUrl(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:93`
  - `public function decodeStringFromRawUrl(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:104`
  - `public function encodeHtmlEntitiesString(string $input, int $flags = ENT_COMPAT|ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string` at `App/Utilities/Traits/EncodingTrait.php:120`
  - `public function encodeSpecialCharsString(string $input, int $flags = ENT_COMPAT|ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string` at `App/Utilities/Traits/EncodingTrait.php:134`
  - `public function decodeHtmlEntitiesString(string $input, int $flags = ENT_COMPAT|ENT_HTML401, string $encoding = 'UTF-8'): string` at `App/Utilities/Traits/EncodingTrait.php:147`
  - `public function quotedPrintableEncodeString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:160`
  - `public function quotedPrintableDecodeString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:171`
  - `public function uuencodeString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:184`
  - `public function uudecodeString(string $input): string` at `App/Utilities/Traits/EncodingTrait.php:195`
  - `public function isValidEncoding(string $input, ?string $encoding = null): bool` at `App/Utilities/Traits/EncodingTrait.php:209`
  - `public function convertStringCase(string $input, int $mode, ?string $encoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:222`
  - `public function convertStringEncoding(string $input, string $toEncoding, ?string $fromEncoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:235`
  - `public function detectStringEncoding(string $input, array|string|null $encodings = null, bool $strict = false): string|false` at `App/Utilities/Traits/EncodingTrait.php:248`
  - `public function setInternalStringEncoding(?string $encoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:259`
  - `public function listSupportedEncodings(): array` at `App/Utilities/Traits/EncodingTrait.php:269`
  - `public function getStringLength(string $input, ?string $encoding = null): int` at `App/Utilities/Traits/EncodingTrait.php:283`
  - `public function findSubstringInString(string $haystack, string $needle, int $offset = 0, ?string $encoding = null): int|false` at `App/Utilities/Traits/EncodingTrait.php:297`
  - `public function findLastSubstringInString(string $haystack, string $needle, ?string $encoding = null): int|false` at `App/Utilities/Traits/EncodingTrait.php:310`
  - `public function convertStringToLower(string $input, ?string $encoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:322`
  - `public function convertStringToUpper(string $input, ?string $encoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:334`
  - `public function getSubstringOfString(string $input, int $start, ?int $length = null, ?string $encoding = null): string` at `App/Utilities/Traits/EncodingTrait.php:348`

### `ErrorTrait`

- Path: `App/Utilities/Traits/ErrorTrait.php`
- FQCN: `App\Utilities\Traits\ErrorTrait`
- Type: `method provider`
- Imports: `ReflectionObject`, `Throwable`, `UnexpectedValueException`
- Composed traits: `ExistenceCheckerTrait`, `TypeCheckerTrait`
- Current consumers: `App/Abstracts/Console/Command.php`, `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Data/SchemaProcessor.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Http/Controller.php`, `App/Abstracts/Http/Middleware.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Http/Service.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Abstracts/Presentation/View.php`, `App/Core/App.php`, `App/Core/Config.php`, `App/Core/Database.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/Session.php`, `App/Utilities/Handlers/LocaleHandler.php`, `App/Utilities/Handlers/MessageFormatterHandler.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Handlers/NormalizeHandler.php`, `App/Utilities/Handlers/NumberFormatterHandler.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`, `App/Utilities/Managers/System/DateTimeManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `2` total (`1` public, `0` protected, `1` private)
  - `public function wrapInTry(callable $callback, string|Throwable|callable|null $wrapException = null): mixed` at `App/Utilities/Traits/ErrorTrait.php:23`
  - `private function resolveErrorManagerInstance(): ?object` at `App/Utilities/Traits/ErrorTrait.php:84`

### `ExistenceCheckerTrait`

- Path: `App/Utilities/Traits/ExistenceCheckerTrait.php`
- FQCN: `App\Utilities\Traits\ExistenceCheckerTrait`
- Type: `method provider`
- Summary: `Provides utility methods to check the existence of various PHP entities such as classes, interfaces,`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Data/SchemaProcessor.php`, `App/Abstracts/Http/Controller.php`, `App/Abstracts/Http/Request.php`, `App/Core/App.php`, `App/Core/Bootstrap.php`, `App/Core/Router.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Managers/System/ErrorManager.php`, `App/Utilities/Managers/System/IteratorManager.php`
- Methods: `7` total (`7` public, `0` protected, `0` private)
  - `public function classExists(string $className): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:22`
  - `public function interfaceExists(string $interfaceName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:33`
  - `public function traitExists(string $traitName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:44`
  - `public function methodExists(object|string $objectOrClass, string $methodName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:56`
  - `public function propertyExists(object|string $objectOrClass, string $propertyName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:68`
  - `public function constantExists(string $className, string $constantName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:80`
  - `public function functionExists(string $functionName): bool` at `App/Utilities/Traits/ExistenceCheckerTrait.php:91`

### `FileCriteriaTrait`

- Path: `App/Utilities/Traits/FileCriteriaTrait.php`
- FQCN: `App\Utilities\Traits\FileCriteriaTrait`
- Type: `wrapper trait`
- Composed traits: `Criteria\FileCriteriaTrait`
- Current consumers: `App/Utilities/Finders/FileFinder.php`
- Methods: none declared directly

### `FileSortTrait`

- Path: `App/Utilities/Traits/FileSortTrait.php`
- FQCN: `App\Utilities\Traits\FileSortTrait`
- Type: `wrapper trait`
- Composed traits: `Sort\FileSortTrait`
- Current consumers: `App/Utilities/Finders/FileFinder.php`
- Methods: none declared directly

### `HashingTrait`

- Path: `App/Utilities/Traits/HashingTrait.php`
- FQCN: `App\Utilities\Traits\HashingTrait`
- Type: `method provider`
- Summary: `Provides utility methods for hashing, key derivation, and secure string operations.`
- Current consumers: `App/Drivers/Cryptography/OpenSSLCrypto.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Security/DatabaseUserProvider.php`
- Methods: `12` total (`12` public, `0` protected, `0` private)
  - `public function hashString(string $data, string $algorithm = 'sha256'): string` at `App/Utilities/Traits/HashingTrait.php:22`
  - `public function hmac(string $data, string $key, string $algorithm = 'sha256', bool $rawOutput = false): string` at `App/Utilities/Traits/HashingTrait.php:36`
  - `public function pbkdf2(string $password, string $salt, int $iterations = 1000, int $length = 32, string $algorithm = 'sha256', bool $rawOutput = false): string` at `App/Utilities/Traits/HashingTrait.php:52`
  - `public function passwordHash(string $password, int|string $algo = PASSWORD_ARGON2ID, array $options = []): string|false` at `App/Utilities/Traits/HashingTrait.php:65`
  - `public function verifyPassword(string $password, string $hash): bool` at `App/Utilities/Traits/HashingTrait.php:77`
  - `public function passwordNeedsRehash(string $hash, int|string $algo = PASSWORD_ARGON2ID, array $options = []): bool` at `App/Utilities/Traits/HashingTrait.php:90`
  - `public function compare(string $known, string $userInput): bool` at `App/Utilities/Traits/HashingTrait.php:102`
  - `public function getAvailableAlgorithms(): array` at `App/Utilities/Traits/HashingTrait.php:112`
  - `public function hashFile(string $filename, string $algorithm = 'sha256', bool $rawOutput = false): string|false` at `App/Utilities/Traits/HashingTrait.php:125`
  - `public function hmacFile(string $filename, string $key, string $algorithm = 'sha256', bool $rawOutput = false): string|false` at `App/Utilities/Traits/HashingTrait.php:139`
  - `public function getHashState(string $algorithm): array` at `App/Utilities/Traits/HashingTrait.php:150`
  - `public function computeRollingHash(string $data, int $type, int $state = 0): int` at `App/Utilities/Traits/HashingTrait.php:163`

### `LocaleTrait`

- Path: `App/Utilities/Traits/LocaleTrait.php`
- FQCN: `App\Utilities\Traits\LocaleTrait`
- Type: `wrapper trait`
- Composed traits: `LocaleUtilityTrait`
- Current consumers: none found outside the traits layer
- Methods: none declared directly

### `LocaleUtilityTrait`

- Path: `App/Utilities/Traits/LocaleUtilityTrait.php`
- FQCN: `App\Utilities\Traits\LocaleUtilityTrait`
- Type: `method provider`
- Summary: `Provides utility functions for handling locale-based string operations.`
- Current consumers: none found outside the traits layer
- Methods: `5` total (`5` public, `0` protected, `0` private)
  - `public function applyLocale(string $locale): string` at `App/Utilities/Traits/LocaleUtilityTrait.php:20`
  - `public function getLocaleSettings(): array` at `App/Utilities/Traits/LocaleUtilityTrait.php:30`
  - `public function localeCompare(string $str1, string $str2): int` at `App/Utilities/Traits/LocaleUtilityTrait.php:43`
  - `public function localeSort(array &$array): bool` at `App/Utilities/Traits/LocaleUtilityTrait.php:55`
  - `public function localeCaseConvert(string $input, int $mode, ?string $locale = null): string` at `App/Utilities/Traits/LocaleUtilityTrait.php:69`

### `LoopTrait`

- Path: `App/Utilities/Traits/LoopTrait.php`
- FQCN: `App\Utilities\Traits\LoopTrait`
- Type: `method provider`
- Summary: `Provides utility methods to replace native loop constructs.`
- Current consumers: `App/Abstracts/Data/Finder.php`
- Methods: `7` total (`7` public, `0` protected, `0` private)
  - `public function each(array $array, callable $callback): string` at `App/Utilities/Traits/LoopTrait.php:21`
  - `public function iterateRange(int $start, int $end, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:37`
  - `public function until(int $start, int $end, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:52`
  - `public function atLeastOnce(int $start, int $end, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:68`
  - `public function repeatLoop(int $times, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:83`
  - `public function through(int $start, int $end, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:98`
  - `public function stepRange(int $start, int $end, int $step, callable $callback): void` at `App/Utilities/Traits/LoopTrait.php:114`

### `ManipulationTrait`

- Path: `App/Utilities/Traits/ManipulationTrait.php`
- FQCN: `App\Utilities\Traits\ManipulationTrait`
- Type: `method provider`
- Summary: `Provides utility functions for string manipulation and array joining operations.`
- Current consumers: `App/Abstracts/Console/Command.php`, `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Database/Model.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Database/Repository.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Abstracts/Presentation/View.php`, `App/Abstracts/Support/PaymentDriver.php`, `App/Console/ConsoleKernel.php`, `App/Core/App.php`, `App/Core/Bootstrap.php`, `App/Core/Config.php`, `App/Core/Database.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/Schema/Blueprint.php`, `App/Core/SeedRunner.php`, `App/Core/Session.php`, `App/Drivers/Cryptography/OpenSSLCrypto.php`, `App/Drivers/Notifications/DatabaseNotificationChannel.php`, `App/Drivers/Passkeys/TestingPasskeyDriver.php`, `App/Drivers/Passkeys/WebAuthnPasskeyDriver.php`, `App/Drivers/Queue/DatabaseQueueDriver.php`, `App/Drivers/Queue/SyncQueueDriver.php`, `App/Drivers/Session/DatabaseSessionDriver.php`, `App/Drivers/Session/EncryptedSessionDriver.php`, `App/Drivers/Session/FileSessionDriver.php`, `App/Drivers/Session/RedisSessionDriver.php`, `App/Modules/UserModule/Services/UserAuthService.php`, `App/Modules/UserModule/Services/UserPasskeyService.php`, `App/Modules/WebModule/Services/PageService.php`, `App/Providers/CacheProvider.php`, `App/Providers/CryptoProvider.php`, `App/Providers/NotificationProvider.php`, `App/Providers/PaymentProvider.php`, `App/Providers/QueueProvider.php`, `App/Providers/ShippingProvider.php`, `App/Utilities/Handlers/CryptoHandler.php`, `App/Utilities/Handlers/DataHandler.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Handlers/SQLHandler.php`, `App/Utilities/Managers/Async/DatabaseFailedJobStore.php`, `App/Utilities/Managers/Async/QueueManager.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Presentation/ThemeManager.php`, `App/Utilities/Managers/Security/DatabaseUserProvider.php`, `App/Utilities/Managers/Security/Gate.php`, `App/Utilities/Managers/Security/HttpSecurityManager.php`, `App/Utilities/Managers/Security/PermissionRegistry.php`, `App/Utilities/Managers/Security/PolicyResolver.php`, `App/Utilities/Managers/Security/SessionGuard.php`, `App/Utilities/Managers/Support/ArchitectureAlignmentManager.php`, `App/Utilities/Managers/Support/AuditLogger.php`, `App/Utilities/Managers/Support/FrameworkDoctor.php`, `App/Utilities/Managers/Support/FrameworkLayerManager.php`, `App/Utilities/Managers/Support/MailManager.php`, `App/Utilities/Managers/Support/NotificationManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`, `App/Utilities/Managers/Support/PaymentManager.php`, `App/Utilities/Managers/System/ErrorManager.php`, `App/Utilities/Managers/System/FileManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `23` total (`23` public, `0` protected, `0` private)
  - `public function splitString(string $delimiter, string $string, int $limit = PHP_INT_MAX): array` at `App/Utilities/Traits/ManipulationTrait.php:24`
  - `public function joinStrings(string $glue, array $pieces): string` at `App/Utilities/Traits/ManipulationTrait.php:36`
  - `public function padString(string $input, int $length, string $padStr = ' ', int $padType = STR_PAD_RIGHT): string` at `App/Utilities/Traits/ManipulationTrait.php:52`
  - `public function replaceText(string|array $search, string|array $replace, string|array $subject, ?int &$count = null): string|array` at `App/Utilities/Traits/ManipulationTrait.php:68`
  - `public function findIgnoreCase(string $haystack, string $needle): string|false` at `App/Utilities/Traits/ManipulationTrait.php:80`
  - `public function findFirst(string $haystack, string $needle): int|false` at `App/Utilities/Traits/ManipulationTrait.php:92`
  - `public function findLast(string $haystack, string $needle): int|false` at `App/Utilities/Traits/ManipulationTrait.php:104`
  - `public function findSubstring(string $haystack, string $needle): string|false` at `App/Utilities/Traits/ManipulationTrait.php:116`
  - `public function compareIgnoreCase(string $str1, string $str2): int` at `App/Utilities/Traits/ManipulationTrait.php:130`
  - `public function length(string $string): int` at `App/Utilities/Traits/ManipulationTrait.php:143`
  - `public function substring(string $string, int $start, ?int $length = null): string` at `App/Utilities/Traits/ManipulationTrait.php:156`
  - `public function splitToArray(string $string, int $length = 1): array` at `App/Utilities/Traits/ManipulationTrait.php:168`
  - `public function toLower(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:181`
  - `public function toUpper(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:192`
  - `public function capitalizeWords(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:203`
  - `public function trimString(string $string, string $characters = " \t\n\r\0\x0B"): string` at `App/Utilities/Traits/ManipulationTrait.php:217`
  - `public function trimLeft(string $string, string $characters = " \t\n\r\0\x0B"): string` at `App/Utilities/Traits/ManipulationTrait.php:229`
  - `public function trimRight(string $string, string $characters = " \t\n\r\0\x0B"): string` at `App/Utilities/Traits/ManipulationTrait.php:241`
  - `public function reverseString(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:254`
  - `public function repeatString(string $input, int $multiplier): string` at `App/Utilities/Traits/ManipulationTrait.php:266`
  - `public function shuffleString(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:279`
  - `public function escapeHtml(string $string): string` at `App/Utilities/Traits/ManipulationTrait.php:292`
  - `public function tokenizeString(string $input, string $delimiters): array` at `App/Utilities/Traits/ManipulationTrait.php:297`

### `MetricsTrait`

- Path: `App/Utilities/Traits/MetricsTrait.php`
- FQCN: `App\Utilities\Traits\MetricsTrait`
- Type: `method provider`
- Summary: `Provides utility functions for measuring similarity and distance between strings.`
- Current consumers: `App/Abstracts/Presentation/Presenter.php`
- Methods: `6` total (`5` public, `0` protected, `1` private)
  - `public function distance(string $str1, string $str2): int` at `App/Utilities/Traits/MetricsTrait.php:21`
  - `public function similarityScore(string $str1, string $str2): float` at `App/Utilities/Traits/MetricsTrait.php:33`
  - `public function hasSoundexMatch(string $str1, string $str2): bool` at `App/Utilities/Traits/MetricsTrait.php:46`
  - `public function metaphoneMatch(string $str1, string $str2): bool` at `App/Utilities/Traits/MetricsTrait.php:58`
  - `public function jaroWinklerMatch(string $str1, string $str2): float` at `App/Utilities/Traits/MetricsTrait.php:71`
  - `private function calculateJaroWinkler(string $str1, string $str2): float` at `App/Utilities/Traits/MetricsTrait.php:84`

### `MoneyFormattingTrait`

- Path: `App/Utilities/Traits/MoneyFormattingTrait.php`
- FQCN: `App\Utilities\Traits\MoneyFormattingTrait`
- Type: `method provider`
- Summary: `Provides a shared framework representation for money stored in minor units.`
- Current consumers: `App/Abstracts/Database/Repository.php`, `App/Abstracts/Http/Service.php`, `App/Utilities/Managers/Commerce/CommerceTotalsCalculator.php`, `App/Utilities/Managers/Commerce/OrderDocumentManager.php`, `App/Utilities/Managers/Commerce/PromotionManager.php`, `App/Utilities/Managers/Commerce/ShippingManager.php`
- Methods: `1` total (`1` public, `0` protected, `0` private)
  - `public function formatMoneyMinor(int $amount, string $currency): string` at `App/Utilities/Traits/MoneyFormattingTrait.php:12`

### `RetrieverTrait`

- Path: `App/Utilities/Traits/RetrieverTrait.php`
- FQCN: `App\Utilities\Traits\RetrieverTrait`
- Type: `method provider`
- Summary: `Provides utility methods for retrieving various PHP entities such as classes, methods, functions,`
- Current consumers: none found outside the traits layer
- Methods: `11` total (`11` public, `0` protected, `0` private)
  - `public function getClass(object $object): string` at `App/Utilities/Traits/RetrieverTrait.php:22`
  - `public function listClassMethods(object|string $class): array` at `App/Utilities/Traits/RetrieverTrait.php:33`
  - `public function getDeclaredClasses(): array` at `App/Utilities/Traits/RetrieverTrait.php:43`
  - `public function getDeclaredInterfaces(): array` at `App/Utilities/Traits/RetrieverTrait.php:53`
  - `public function getDefinedFunctions(): array` at `App/Utilities/Traits/RetrieverTrait.php:63`
  - `public function getDefinedVars(): array` at `App/Utilities/Traits/RetrieverTrait.php:73`
  - `public function getIncludedFiles(): array` at `App/Utilities/Traits/RetrieverTrait.php:83`
  - `public function getLoadedExtensions(): array` at `App/Utilities/Traits/RetrieverTrait.php:93`
  - `public function getObjectVars(object $object): array` at `App/Utilities/Traits/RetrieverTrait.php:104`
  - `public function getParentClass(object|string $objectOrClass): string|false` at `App/Utilities/Traits/RetrieverTrait.php:115`
  - `public function getResources(?string $type = null): array` at `App/Utilities/Traits/RetrieverTrait.php:126`

### `TypeCheckerTrait`

- Path: `App/Utilities/Traits/TypeCheckerTrait.php`
- FQCN: `App\Utilities\Traits\TypeCheckerTrait`
- Type: `method provider`
- Summary: `Provides utility methods to check the types and properties of various PHP entities.`
- Current consumers: `App/Abstracts/Console/Command.php`, `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Data/SchemaProcessor.php`, `App/Abstracts/Database/Model.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Database/Repository.php`, `App/Abstracts/Http/Controller.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Abstracts/Presentation/Resource.php`, `App/Abstracts/Presentation/View.php`, `App/Core/App.php`, `App/Core/Config.php`, `App/Core/Database.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/SeedRunner.php`, `App/Core/Session.php`, `App/Drivers/Cryptography/OpenSSLCrypto.php`, `App/Drivers/Passkeys/TestingPasskeyDriver.php`, `App/Drivers/Passkeys/WebAuthnPasskeyDriver.php`, `App/Drivers/Queue/DatabaseQueueDriver.php`, `App/Modules/UserModule/Services/UserAuthService.php`, `App/Modules/UserModule/Services/UserPasskeyService.php`, `App/Utilities/Handlers/DataHandler.php`, `App/Utilities/Handlers/DataStructureHandler.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Managers/Async/DatabaseFailedJobStore.php`, `App/Utilities/Managers/Async/EventDispatcher.php`, `App/Utilities/Managers/Async/QueueManager.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/Data/SessionManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Presentation/ThemeManager.php`, `App/Utilities/Managers/Security/DatabaseUserProvider.php`, `App/Utilities/Managers/Security/Gate.php`, `App/Utilities/Managers/Security/PermissionRegistry.php`, `App/Utilities/Managers/Security/SessionGuard.php`, `App/Utilities/Managers/Support/ArchitectureAlignmentManager.php`, `App/Utilities/Managers/Support/AuditLogger.php`, `App/Utilities/Managers/Support/FrameworkDoctor.php`, `App/Utilities/Managers/Support/NotificationManager.php`, `App/Utilities/Managers/Support/PasskeyManager.php`, `App/Utilities/Managers/System/ErrorManager.php`, `App/Utilities/Managers/System/FileManager.php`, `App/Utilities/Managers/System/IteratorManager.php`, `App/Utilities/Managers/System/ReflectionManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `24` total (`24` public, `0` protected, `0` private)
  - `public function isArray(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:21`
  - `public function isBool(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:32`
  - `public function isCallable(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:43`
  - `public function isCountable(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:54`
  - `public function isDirectory(string $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:65`
  - `public function isFile(string $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:76`
  - `public function isFloat(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:87`
  - `public function isInt(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:98`
  - `public function isIterable(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:109`
  - `public function isNull(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:120`
  - `public function isNumeric(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:131`
  - `public function isObject(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:142`
  - `public function isResource(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:153`
  - `public function isScalar(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:164`
  - `public function isIntegerOrNull(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:175`
  - `public function isString(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:186`
  - `public function isLink(string $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:197`
  - `public function isSubclassOf(object|string $objectOrClass, string $className): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:209`
  - `public function isUploadedFile(string $fileName): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:220`
  - `public function isWritable(string $fileName): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:231`
  - `public function isReadable(string $fileName): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:242`
  - `public function isEmpty(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:253`
  - `public function isSet(mixed $value): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:264`
  - `public function isInArray(mixed $needle, array $haystack, bool $strict = false): bool` at `App/Utilities/Traits/TypeCheckerTrait.php:281`

## Criteria

### `DirectoryCriteriaTrait`

- Path: `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php`
- FQCN: `App\Utilities\Traits\Criteria\DirectoryCriteriaTrait`
- Type: `method provider`
- Summary: `Provides filtering utilities for directory operations. This trait includes`
- Imports: `App\Utilities\Traits\CheckerTrait`, `App\Utilities\Traits\ManipulationTrait`, `App\Utilities\Traits\TypeCheckerTrait`, `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `CheckerTrait`, `ManipulationTrait`, `PatternTrait`, `TypeCheckerTrait`
- Current consumers: none found outside the traits layer
- Methods: `17` total (`0` public, `16` protected, `1` private)
  - `protected function filterByPath($fileInfo, string $path): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:44`
  - `protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:57`
  - `protected function filterByType($fileInfo, string $type = 'directory'): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:72`
  - `protected function filterByOwner($fileInfo, int $owner): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:84`
  - `protected function filterByGroup($fileInfo, int $group): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:96`
  - `protected function filterByPermissions($fileInfo, int $permissions): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:108`
  - `protected function filterByModifiedTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:120`
  - `protected function filterByAccessedTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:132`
  - `protected function filterByCreationTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:144`
  - `protected function filterByDepth($fileInfo, int $maxDepth): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:156`
  - `protected function filterBySymlink($fileInfo): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:167`
  - `protected function filterByExecutable($fileInfo): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:178`
  - `protected function filterByWritable($fileInfo): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:189`
  - `protected function filterByReadable($fileInfo): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:200`
  - `protected function filterByPatternName($fileInfo, string $pattern): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:212`
  - `protected function filterByPatternPath($fileInfo, string $pattern): bool` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:224`
  - `private function resolveDirectoryPath($fileInfo): string` at `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php:229`

### `FileCriteriaTrait`

- Path: `App/Utilities/Traits/Criteria/FileCriteriaTrait.php`
- FQCN: `App\Utilities\Traits\Criteria\FileCriteriaTrait`
- Type: `method provider`
- Summary: `Provides filtering utilities for file operations. This trait includes`
- Imports: `App\Utilities\Traits\CheckerTrait`, `App\Utilities\Traits\ManipulationTrait`, `App\Utilities\Traits\TypeCheckerTrait`, `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `CheckerTrait`, `ManipulationTrait`, `PatternTrait`, `TypeCheckerTrait`
- Current consumers: none found outside the traits layer
- Methods: `20` total (`0` public, `19` protected, `1` private)
  - `protected function filterByPath($fileInfo, string $path): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:47`
  - `protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:60`
  - `protected function filterByExtension($fileInfo, string $extension): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:75`
  - `protected function filterByType($fileInfo, string $type = 'file'): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:87`
  - `protected function filterBySize($fileInfo, int $size): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:99`
  - `protected function filterByPermissions($fileInfo, int $permissions): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:111`
  - `protected function filterByOwner($fileInfo, int $owner): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:123`
  - `protected function filterByGroup($fileInfo, int $group): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:135`
  - `protected function filterByModifiedTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:147`
  - `protected function filterByAccessedTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:159`
  - `protected function filterByCreationTime($fileInfo, int $timestamp): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:171`
  - `protected function filterBySymlink($fileInfo): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:182`
  - `protected function filterByDepth($fileInfo, int $maxDepth): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:194`
  - `protected function filterByExecutable($fileInfo): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:205`
  - `protected function filterByWritable($fileInfo): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:216`
  - `protected function filterByReadable($fileInfo): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:227`
  - `protected function filterByPatternName($fileInfo, string $pattern): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:239`
  - `protected function filterByPatternExtension($fileInfo, string $pattern): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:251`
  - `protected function filterByPatternPath($fileInfo, string $pattern): bool` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:263`
  - `private function resolveFilePath($fileInfo): string` at `App/Utilities/Traits/Criteria/FileCriteriaTrait.php:268`

## Filters

### `FiltrationTrait`

- Path: `App/Utilities/Traits/Filters/FiltrationTrait.php`
- FQCN: `App\Utilities\Traits\Filters\FiltrationTrait`
- Type: `method provider`
- Summary: `Provides a convenient wrapper around PHP's filtering functions.`
- Current consumers: `App/Core/App.php`, `App/Core/Database.php`
- Methods: `5` total (`5` public, `0` protected, `0` private)
  - `public function var(mixed $variable, int $filter = FILTER_DEFAULT, array|int|null $options = null): mixed` at `App/Utilities/Traits/Filters/FiltrationTrait.php:22`
  - `public function varArray(array $data, array $filters, bool $addEmpty = true): array|false|null` at `App/Utilities/Traits/Filters/FiltrationTrait.php:35`
  - `public function input(int $type, string $variableName, int $filter = FILTER_DEFAULT, array|int|null $options = null): mixed` at `App/Utilities/Traits/Filters/FiltrationTrait.php:49`
  - `public function inputArray(int $type, array $filters, bool $addEmpty = true): array|false|null` at `App/Utilities/Traits/Filters/FiltrationTrait.php:62`
  - `public function withCallback(mixed $variable, callable $callback): mixed` at `App/Utilities/Traits/Filters/FiltrationTrait.php:74`

### `SanitationFilterTrait`

- Path: `App/Utilities/Traits/Filters/SanitationFilterTrait.php`
- FQCN: `App\Utilities\Traits\Filters\SanitationFilterTrait`
- Type: `method provider`
- Summary: `Provides methods to sanitize various data types using PHP filters with flexible flag handling.`
- Imports: `InvalidArgumentException`, `App\Utilities\Traits\ArrayTrait`
- Composed traits: `FiltrationTrait`, `ArrayTrait`
- Current consumers: none found outside the traits layer
- Properties:
  - `public readonly array $filters` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:20`
  - `public readonly array $flags` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:21`
- Methods: `10` total (`9` public, `0` protected, `1` private)
  - `public function initializeSanitationFilters(): void` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:26`
  - `public function sanitizeEncoded(string $input, array $flags = []): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:58`
  - `public function sanitizeString(string $input, array $flags = []): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:70`
  - `public function sanitizeEmail(string $input): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:81`
  - `public function sanitizeUrlWithFilter(string $input): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:92`
  - `public function sanitizeInt(string $input, array $flags = []): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:104`
  - `public function sanitizeFloatWithFilter(string $input, array $flags = []): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:116`
  - `public function sanitizeAddSlashes(string $input): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:127`
  - `public function sanitizeFullSpecialChars(string $input, array $flags = []): string` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:139`
  - `private function getFilterOptions(string $filter, array $flagKeys): array` at `App/Utilities/Traits/Filters/SanitationFilterTrait.php:151`

### `SanitationTrait`

- Path: `App/Utilities/Traits/Filters/SanitationTrait.php`
- FQCN: `App\Utilities\Traits\Filters\SanitationTrait`
- Type: `wrapper trait`
- Composed traits: `SanitationFilterTrait`
- Current consumers: `App/Utilities/Sanitation/GeneralSanitizer.php`
- Methods: none declared directly

### `ValidationFilterTrait`

- Path: `App/Utilities/Traits/Filters/ValidationFilterTrait.php`
- FQCN: `App\Utilities\Traits\Filters\ValidationFilterTrait`
- Type: `method provider`
- Summary: `Provides robust methods for validating various data types using PHP's filter extensions.`
- Imports: `InvalidArgumentException`, `App\Utilities\Traits\ArrayTrait`
- Composed traits: `FiltrationTrait`, `ArrayTrait`
- Current consumers: none found outside the traits layer
- Properties:
  - `public readonly array $filters` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:25`
  - `public readonly array $flags` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:32`
- Methods: `11` total (`10` public, `0` protected, `1` private)
  - `public function initializeValidationFilters(): void` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:37`
  - `public function validateBoolean($input): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:70`
  - `public function validateEmail(string $input): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:81`
  - `public function validateFloatWithFilter(string $input, array $flags = []): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:93`
  - `public function validateIntWithFilter(string $input, array $flags = []): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:105`
  - `public function validateIp(string $input, array $flags = []): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:117`
  - `public function validateMac(string $input): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:128`
  - `public function validateRegexp(string $input, string $pattern): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:140`
  - `public function validateUrlWithFilter(string $input, array $flags = []): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:152`
  - `public function validateDomain(string $input): bool` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:163`
  - `private function getFilterOptions(array $flagKeys = []): array` at `App/Utilities/Traits/Filters/ValidationFilterTrait.php:174`

### `ValidationTrait`

- Path: `App/Utilities/Traits/Filters/ValidationTrait.php`
- FQCN: `App\Utilities\Traits\Filters\ValidationTrait`
- Type: `wrapper trait`
- Composed traits: `ValidationFilterTrait`
- Current consumers: `App/Utilities/Validation/GeneralValidator.php`
- Methods: none declared directly

## Iterator

### `IteratorTrait`

- Path: `App/Utilities/Traits/Iterator/IteratorTrait.php`
- FQCN: `App\Utilities\Traits\Iterator\IteratorTrait`
- Type: `method provider`
- Summary: `Methods for Standard Iterators.`
- Imports: `AppendIterator`, `ArrayIterator`, `CachingIterator`, `CallbackFilterIterator`, `DirectoryIterator`, `EmptyIterator`, `FilesystemIterator`, `FilterIterator`, `GlobIterator`, `InfiniteIterator`, `IteratorIterator`, `LimitIterator`, `MultipleIterator`, `NoRewindIterator`, `ParentIterator`, `RegexIterator`, `SeekableIterator`
- Current consumers: `App/Utilities/Managers/System/IteratorManager.php`
- Properties:
  - `private readonly array $iteratorSettings` at `App/Utilities/Traits/Iterator/IteratorTrait.php:36`
- Methods: `18` total (`18` public, `0` protected, `0` private)
  - `public function initializeIteratorTrait(): void` at `App/Utilities/Traits/Iterator/IteratorTrait.php:38`
  - `public function AppendIterator(\Iterator ...$iterators): AppendIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:165`
  - `public function ArrayIterator(array $data, array $settings = []): ArrayIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:183`
  - `public function CachingIterator(\Iterator $iterator, array $settings = []): CachingIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:198`
  - `public function CallbackFilterIterator(\Iterator $iterator, callable $callback): CallbackFilterIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:213`
  - `public function DirectoryIterator(string $path): DirectoryIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:224`
  - `public function EmptyIterator(): EmptyIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:234`
  - `public function FilesystemIterator(string $path, array $settings = []): FilesystemIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:246`
  - `public function FilterIterator(\Iterator $iterator): FilterIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:260`
  - `public function GlobIterator(string $pattern, int $flags = 0): GlobIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:272`
  - `public function InfiniteIterator(\Iterator $iterator): InfiniteIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:283`
  - `public function IteratorIterator(\Iterator $iterator): IteratorIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:294`
  - `public function LimitIterator(\Iterator $iterator, int $offset = 0, int $count = -1): LimitIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:307`
  - `public function MultipleIterator(array $settings = []): MultipleIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:318`
  - `public function NoRewindIterator(\Iterator $iterator): NoRewindIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:331`
  - `public function ParentIterator(\Iterator $iterator): ParentIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:342`
  - `public function RegexIterator(\Iterator $iterator, string $regex, array $settings = []): RegexIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:355`
  - `public function SeekableIterator(\Iterator $iterator): SeekableIterator` at `App/Utilities/Traits/Iterator/IteratorTrait.php:370`

### `RecursiveIteratorTrait`

- Path: `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php`
- FQCN: `App\Utilities\Traits\Iterator\RecursiveIteratorTrait`
- Type: `method provider`
- Summary: `Methods for Recursive Iterators.`
- Imports: `RecursiveArrayIterator`, `RecursiveCachingIterator`, `RecursiveCallbackFilterIterator`, `RecursiveDirectoryIterator`, `RecursiveFilterIterator`, `RecursiveIteratorIterator`, `RecursiveRegexIterator`, `RecursiveTreeIterator`
- Current consumers: `App/Utilities/Managers/System/IteratorManager.php`
- Properties:
  - `private readonly array $recursiveIteratorSettings` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:27`
- Methods: `9` total (`9` public, `0` protected, `0` private)
  - `public function initializeRecursiveIteratorTrait(): void` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:29`
  - `public function RecursiveArrayIterator(array $data, array $settings = []): RecursiveArrayIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:130`
  - `public function RecursiveCachingIterator($iterator, array $settings = []): RecursiveCachingIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:145`
  - `public function RecursiveCallbackFilterIterator($iterator, callable $callback): RecursiveCallbackFilterIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:160`
  - `public function RecursiveDirectoryIterator(string $path, array $settings = []): RecursiveDirectoryIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:175`
  - `public function RecursiveFilterIterator($iterator): RecursiveFilterIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:190`
  - `public function RecursiveIteratorIterator($iterator, array $settings = []): RecursiveIteratorIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:202`
  - `public function RecursiveRegexIterator($iterator, string $regex, array $settings = []): RecursiveRegexIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:224`
  - `public function RecursiveTreeIterator($iterator, array $settings = []): RecursiveTreeIterator` at `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php:240`

## Patterns

### `PatternTrait`

- Path: `App/Utilities/Traits/Patterns/PatternTrait.php`
- FQCN: `App\Utilities\Traits\Patterns\PatternTrait`
- Type: `method provider`
- Summary: `Provides a convenient wrapper around PHP's preg_* functions for regular expression operations.`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Crypto.php`, `App/Abstracts/Data/Finder.php`, `App/Abstracts/Database/Model.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Database/Repository.php`, `App/Abstracts/Http/Response.php`, `App/Core/Config.php`, `App/Core/Database.php`, `App/Core/MigrationRunner.php`, `App/Core/Router.php`, `App/Core/SeedRunner.php`, `App/Drivers/Cryptography/OpenSSLCrypto.php`, `App/Providers/CacheProvider.php`, `App/Providers/CryptoProvider.php`, `App/Providers/ModuleProvider.php`, `App/Providers/NotificationProvider.php`, `App/Providers/PaymentProvider.php`, `App/Providers/QueueProvider.php`, `App/Providers/ShippingProvider.php`, `App/Utilities/Handlers/DataHandler.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Handlers/SQLHandler.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/CryptoManager.php`, `App/Utilities/Managers/Presentation/AssetManager.php`, `App/Utilities/Managers/Presentation/HtmlManager.php`, `App/Utilities/Managers/Presentation/TemplateEngine.php`, `App/Utilities/Managers/Presentation/ThemeManager.php`, `App/Utilities/Managers/Support/MailManager.php`, `App/Utilities/Managers/System/FileManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Methods: `6` total (`6` public, `0` protected, `0` private)
  - `public function match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false` at `App/Utilities/Traits/Patterns/PatternTrait.php:24`
  - `public function matchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false` at `App/Utilities/Traits/Patterns/PatternTrait.php:39`
  - `public function replaceByPattern(string|array $pattern, string|array $replacement, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null` at `App/Utilities/Traits/Patterns/PatternTrait.php:54`
  - `public function replaceCallback(string|array $pattern, callable $callback, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null` at `App/Utilities/Traits/Patterns/PatternTrait.php:69`
  - `public function splitByPattern(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|false` at `App/Utilities/Traits/Patterns/PatternTrait.php:83`
  - `public function quote(string $str, ?string $delimiter = null): string` at `App/Utilities/Traits/Patterns/PatternTrait.php:95`

### `SanitationPatternTrait`

- Path: `App/Utilities/Traits/Patterns/SanitationPatternTrait.php`
- FQCN: `App\Utilities\Traits\Patterns\SanitationPatternTrait`
- Type: `method provider`
- Summary: `Provides a predefined collection of regular expression patterns for sanitizing various input types.`
- Imports: `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `PatternTrait`
- Current consumers: `App/Utilities/Sanitation/PatternSanitizer.php`
- Properties:
  - `public readonly array $patterns` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:24`
- Methods: `27` total (`27` public, `0` protected, `0` private)
  - `public function initializeSanitationPatterns(): void` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:31`
  - `public function sanitizeName(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:82`
  - `public function sanitizeSsn(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:93`
  - `public function sanitizePhoneUs(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:104`
  - `public function sanitizePhoneIntl(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:115`
  - `public function sanitizeZipUs(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:126`
  - `public function sanitizeZipUk(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:137`
  - `public function sanitizeHex(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:148`
  - `public function sanitizeBinary(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:159`
  - `public function sanitizeOctal(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:170`
  - `public function sanitizeCreditCard(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:181`
  - `public function sanitizeIsbn(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:192`
  - `public function sanitizeCurrencyUsd(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:203`
  - `public function sanitizeFileName(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:214`
  - `public function sanitizeDirectory(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:225`
  - `public function sanitizePathUnix(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:236`
  - `public function sanitizeFileExt(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:247`
  - `public function sanitizeSlug(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:258`
  - `public function sanitizeUrlByPattern(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:269`
  - `public function sanitizeIpv4(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:280`
  - `public function sanitizeIpv6(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:291`
  - `public function sanitizeIntPos(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:302`
  - `public function sanitizeFloatByPattern(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:313`
  - `public function sanitizePercent(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:324`
  - `public function sanitizeAlpha(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:335`
  - `public function sanitizeAlphaNum(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:346`
  - `public function sanitizeHashtag(string $input): ?string` at `App/Utilities/Traits/Patterns/SanitationPatternTrait.php:357`

### `ValidationPatternTrait`

- Path: `App/Utilities/Traits/Patterns/ValidationPatternTrait.php`
- FQCN: `App\Utilities\Traits\Patterns\ValidationPatternTrait`
- Type: `method provider`
- Summary: `Provides a collection of regular expression patterns for validating various types of input data.`
- Imports: `App\Utilities\Traits\Filters\FiltrationTrait`, `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `PatternTrait`, `FiltrationTrait`
- Current consumers: `App/Utilities/Validation/PatternValidator.php`
- Properties:
  - `public readonly array $patterns` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:25`
- Methods: `44` total (`44` public, `0` protected, `0` private)
  - `public function initializeValidationPatterns(): void` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:33`
  - `public function validateName(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:101`
  - `public function validateSsn(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:112`
  - `public function validatePhoneUs(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:123`
  - `public function validatePhoneIntl(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:134`
  - `public function validateHexadecimal(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:145`
  - `public function validateHexOnly(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:156`
  - `public function validateBinary(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:167`
  - `public function validateOctal(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:178`
  - `public function validateCreditCard(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:189`
  - `public function validateIsbn10(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:200`
  - `public function validateIban(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:211`
  - `public function validateBic(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:222`
  - `public function validateEthereumAddress(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:233`
  - `public function validateBitcoinAddress(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:244`
  - `public function validateFileName(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:255`
  - `public function validateDirectory(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:266`
  - `public function validatePathUnix(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:277`
  - `public function validatePathWindows(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:288`
  - `public function validateFileExt(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:299`
  - `public function validateImageExt(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:310`
  - `public function validateAudioExt(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:321`
  - `public function validateVideoExt(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:332`
  - `public function validateSlug(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:343`
  - `public function validateUrlByPattern(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:354`
  - `public function validateUrlPort(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:365`
  - `public function validateIpv4(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:377`
  - `public function validateIpv6(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:388`
  - `public function validateZipUs(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:399`
  - `public function validateZipUk(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:410`
  - `public function validateIntPos(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:421`
  - `public function validateIntNeg(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:432`
  - `public function validateIntByPattern(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:443`
  - `public function validateFloatPos(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:454`
  - `public function validateFloatNeg(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:465`
  - `public function validateFloatByPattern(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:476`
  - `public function validateScientific(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:487`
  - `public function validateAlpha(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:498`
  - `public function validateAlphaSpace(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:509`
  - `public function validateAlphaDash(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:520`
  - `public function validateAlphaNum(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:531`
  - `public function validateAlphaNumSpace(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:542`
  - `public function validateHashtag(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:553`
  - `public function validateTwitterHandle(string $input): bool` at `App/Utilities/Traits/Patterns/ValidationPatternTrait.php:564`

## Query

### `DataQueryTrait`

- Path: `App/Utilities/Traits/Query/DataQueryTrait.php`
- FQCN: `App\Utilities\Traits\Query\DataQueryTrait`
- Type: `method provider`
- Summary: `Fluent API surface for the data query builder.`
- Current consumers: `App/Utilities/Query/DataQuery.php`
- Methods: `78` total (`75` public, `0` protected, `3` private)
  - `private function chainCondition(array $condition, string $target = 'where'): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:15`
  - `private function chainConditionGroup(string $logic, array $conditions, string $target = 'where'): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:22`
  - `private function chainJoin(string $type, string $table, array $onConditions = [], array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:29`
  - `public function from(string $table): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:41`
  - `public function where(string $column, string $operator, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:48`
  - `public function having(string $column, string $operator, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:53`
  - `public function on(string $column, string $operator, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:58`
  - `public function whereFilter(string $column, string $operator, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:63`
  - `public function fetch(int $rowCount): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:68`
  - `public function returning(array $columns): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:75`
  - `public function with(string $queryAlias, callable|string|object $subquery): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:82`
  - `public function not(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:92`
  - `public function and(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:97`
  - `public function or(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:102`
  - `public function xor(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:107`
  - `public function andNot(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:112`
  - `public function orNot(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:117`
  - `public function allOf(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:122`
  - `public function anyOf(array $conditions): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:127`
  - `public function equal(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:132`
  - `public function notEqual(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:137`
  - `public function notEqualAlt(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:142`
  - `public function nullSafeEqual(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:147`
  - `public function greaterThan(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:152`
  - `public function greaterThanOrEqual(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:157`
  - `public function lessThan(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:162`
  - `public function lessThanOrEqual(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:167`
  - `public function isDistinctFrom(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:172`
  - `public function notDistinctFrom(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:177`
  - `public function whereNull(string $column): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:182`
  - `public function whereNotNull(string $column): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:187`
  - `public function in(string $column, array $values): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:192`
  - `public function notIn(string $column, array $values): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:197`
  - `public function exists(callable|string|object $subquery): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:202`
  - `public function notExists(callable|string|object $subquery): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:207`
  - `public function except(callable|string|object $query): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:212`
  - `public function intersectWith(callable|string|object $query): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:222`
  - `public function minus(callable|string|object $query): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:232`
  - `public function union(callable|string|object $query): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:242`
  - `public function unionAll(callable|string|object $query): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:252`
  - `public function between(string $column, mixed $start, mixed $end): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:262`
  - `public function notBetween(string $column, mixed $start, mixed $end): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:267`
  - `public function like(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:272`
  - `public function notLike(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:277`
  - `public function iLike(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:282`
  - `public function regexp(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:287`
  - `public function notRegexp(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:292`
  - `public function whereSoundsLike(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:297`
  - `public function similarTo(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:302`
  - `public function notSimilarTo(string $column, string $pattern): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:307`
  - `public function connectBy(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:312`
  - `public function startWith(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:319`
  - `public function connectByPrior(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:326`
  - `public function prior(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:333`
  - `public function withRecursive(string $column, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:340`
  - `public function distinctOn(array $columns): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:347`
  - `public function overlaps(string $column, string $operator, mixed $value): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:354`
  - `public function forUpdate(): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:366`
  - `public function forShare(): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:373`
  - `public function joinTable(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:380`
  - `public function fullOuterJoin(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:385`
  - `public function leftJoin(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:390`
  - `public function rightJoin(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:395`
  - `public function innerJoin(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:400`
  - `public function crossJoin(string $table, array $onConditions = [], array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:405`
  - `public function naturalJoin(string $table, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:410`
  - `public function fullJoin(string $table, array $onConditions, array $columns = []): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:415`
  - `public function orderBy(string $column, string $direction = 'ASC'): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:420`
  - `public function groupBy(array $columns): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:431`
  - `public function groupingSets(array $sets): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:441`
  - `public function cube(array $columns): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:451`
  - `public function rollup(array $columns): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:461`
  - `public function limit(int $limit): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:471`
  - `public function offset(int $offset): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:478`
  - `public function insert(string $table, array $data): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:485`
  - `public function select(array $columns = ['*']): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:493`
  - `public function update(string $table, array $data): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:500`
  - `public function delete(string $table): self` at `App/Utilities/Traits/Query/DataQueryTrait.php:508`

### `SchemaQueryTrait`

- Path: `App/Utilities/Traits/Query/SchemaQueryTrait.php`
- FQCN: `App\Utilities\Traits\Query\SchemaQueryTrait`
- Type: `method provider`
- Current consumers: `App/Utilities/Query/SchemaQuery.php`
- Methods: `41` total (`40` public, `0` protected, `1` private)
  - `private function chainSchema(callable $callback): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:9`
  - `public function addColumn(string $table, string $column, string $definition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:16`
  - `public function removeColumn(string $table, string $column): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:21`
  - `public function renameColumn(string $table, string $oldName, string $newName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:26`
  - `public function modifyColumn(string $table, string $column, string $newDefinition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:31`
  - `public function createTable(string $table, array $columns, array $constraints = []): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:36`
  - `public function renameTable(string $oldName, string $newName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:41`
  - `public function truncateTable(string $table): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:46`
  - `public function dropTable(string $table): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:51`
  - `public function alterTable(string $table, array $options): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:56`
  - `public function addIndex(string $table, string $indexName, array $columns, string $indexType = ''): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:61`
  - `public function dropIndex(string $table, string $indexName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:68`
  - `public function renameIndex(string $table, string $oldIndexName, string $newIndexName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:73`
  - `public function setConstraint(string $table, string $constraint, array $definition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:80`
  - `public function dropConstraint(string $table, string $constraint): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:87`
  - `public function renameConstraint(string $table, string $oldConstraint, string $newConstraint): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:92`
  - `public function setUnique(string $table, array $columns): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:99`
  - `public function dropUnique(string $table, string $constraint): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:104`
  - `public function setCheck(string $table, string $constraint, string $condition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:109`
  - `public function dropCheck(string $table, string $constraint): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:116`
  - `public function setForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $onDelete = 'RESTRICT', string $onUpdate = 'RESTRICT'): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:121`
  - `public function dropForeign(string $table, string $foreignKeyName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:143`
  - `public function addPrimary(string $table, array $columns): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:148`
  - `public function dropPrimary(string $table): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:153`
  - `public function setDefault(string $table, string $column, mixed $defaultValue): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:158`
  - `public function dropDefault(string $table, string $column): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:165`
  - `public function createView(string $viewName, string $selectQuery): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:170`
  - `public function dropView(string $viewName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:175`
  - `public function alterView(string $viewName, string $selectQuery): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:180`
  - `public function createTrigger(string $triggerName, string $table, string $timing, string $event, string $statement): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:185`
  - `public function dropTrigger(string $triggerName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:197`
  - `public function alterTrigger(string $triggerName, string $table, string $timing, string $event, string $statement): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:202`
  - `public function createDatabase(string $database): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:214`
  - `public function dropDatabase(string $database): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:219`
  - `public function alterDatabase(string $database, array $options): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:224`
  - `public function createProcedure(string $procedureName, string $definition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:229`
  - `public function dropProcedure(string $procedureName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:234`
  - `public function createFunction(string $functionName, string $definition): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:239`
  - `public function dropFunction(string $functionName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:244`
  - `public function createSequence(string $sequenceName, array $options = []): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:249`
  - `public function dropSequence(string $sequenceName): self` at `App/Utilities/Traits/Query/SchemaQueryTrait.php:254`

## Reflection

### `ReflectionAttributeTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionAttributeTrait`
- Type: `method provider`
- Summary: `Covers ReflectionAttribute methods.`
- Imports: `ReflectionAttribute`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `5` total (`5` public, `0` protected, `0` private)
  - `public function getAttributeArguments(ReflectionAttribute $attribute): array` at `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php:22`
  - `public function getAttributeName(ReflectionAttribute $attribute): string` at `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php:33`
  - `public function getAttributeTarget(ReflectionAttribute $attribute): int` at `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php:44`
  - `public function isAttributeRepeated(ReflectionAttribute $attribute): bool` at `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php:55`
  - `public function newAttributeInstance(ReflectionAttribute $attribute): object` at `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php:66`

### `ReflectionClassTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionClassTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionClassTrait`
- Type: `method provider`
- Summary: `Covers ReflectionClass, ReflectionClassConstant, and ReflectionExtension.`
- Imports: `ReflectionClass`, `ReflectionObject`, `ReflectionMethod`, `ReflectionProperty`, `ReflectionClassConstant`, `ReflectionExtension`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `63` total (`63` public, `0` protected, `0` private)
  - `public function createClass(object|string $class): ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:30`
  - `public function getClassAttributes(ReflectionClass $reflectionClass, ?string $name = null, int $flags = 0): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:43`
  - `public function getClassConstant(ReflectionClass $reflectionClass, string $name): mixed` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:55`
  - `public function getClassConstants(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:66`
  - `public function getClassConstructor(ReflectionClass $reflectionClass): ?ReflectionMethod` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:77`
  - `public function getClassDefaultProperties(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:88`
  - `public function getClassDocComment(ReflectionClass $reflectionClass): string|false` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:99`
  - `public function getClassEndLine(ReflectionClass $reflectionClass): int|false` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:110`
  - `public function getClassExtension(ReflectionClass $reflectionClass): ?ReflectionExtension` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:121`
  - `public function getClassExtensionName(ReflectionClass $reflectionClass): string|false` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:132`
  - `public function getClassFileName(ReflectionClass $reflectionClass): string|false` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:143`
  - `public function getClassInterfaceNames(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:154`
  - `public function getClassInterfaces(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:165`
  - `public function getClassLazyInitializer(ReflectionClass $reflectionClass): ?callable` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:176`
  - `public function getClassMethod(ReflectionClass $reflectionClass, string $name): ReflectionMethod` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:188`
  - `public function getReflectedClassMethods(ReflectionClass $reflectionClass, ?int $filter = null): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:200`
  - `public function getClassModifiers(ReflectionClass $reflectionClass): int` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:211`
  - `public function getClassName(ReflectionClass $reflectionClass): string` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:222`
  - `public function getClassNamespaceName(ReflectionClass $reflectionClass): string` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:233`
  - `public function getClassParent(ReflectionClass $reflectionClass): ?ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:244`
  - `public function getClassProperties(ReflectionClass $reflectionClass, ?int $filter = null): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:256`
  - `public function getClassProperty(ReflectionClass $reflectionClass, string $name): ReflectionProperty` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:268`
  - `public function getClassReflectionConstant(ReflectionClass $reflectionClass, string $name): ?ReflectionClassConstant` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:280`
  - `public function getClassReflectionConstants(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:291`
  - `public function getClassShortName(ReflectionClass $reflectionClass): string` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:302`
  - `public function getClassStartLine(ReflectionClass $reflectionClass): int|false` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:313`
  - `public function getClassStaticProperties(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:324`
  - `public function getClassStaticPropertyValue(ReflectionClass $reflectionClass, string $name): mixed` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:336`
  - `public function getClassTraitAliases(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:347`
  - `public function getClassTraitNames(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:358`
  - `public function getClassTraits(ReflectionClass $reflectionClass): array` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:369`
  - `public function hasClassConstant(ReflectionClass $reflectionClass, string $name): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:381`
  - `public function hasClassMethod(ReflectionClass $reflectionClass, string $name): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:393`
  - `public function hasClassProperty(ReflectionClass $reflectionClass, string $name): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:405`
  - `public function implementsClassInterface(ReflectionClass $reflectionClass, string $interface): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:417`
  - `public function initializeClassLazyObject(ReflectionClass $reflectionClass, object $object): void` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:429`
  - `public function isClassInNamespace(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:440`
  - `public function isClassAbstract(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:451`
  - `public function isClassAnonymous(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:462`
  - `public function isClassCloneable(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:473`
  - `public function isClassEnum(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:484`
  - `public function isClassFinal(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:495`
  - `public function isClassInstance(ReflectionClass $reflectionClass, object $object): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:507`
  - `public function isClassInstantiable(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:518`
  - `public function isClassInterface(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:529`
  - `public function isClassInternal(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:540`
  - `public function isClassIterable(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:551`
  - `public function isClassIterateable(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:562`
  - `public function isClassReadOnly(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:573`
  - `public function isClassSubclassOf(ReflectionClass $reflectionClass, string $class): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:585`
  - `public function isClassTrait(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:596`
  - `public function isClassUninitializedLazyObject(ReflectionClass $reflectionClass, object $object): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:608`
  - `public function isClassUserDefined(ReflectionClass $reflectionClass): bool` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:619`
  - `public function markClassLazyInitialized(ReflectionClass $reflectionClass, object $object): void` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:631`
  - `public function newClassInstance(ReflectionClass $reflectionClass, mixed ...$args): object` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:644`
  - `public function newClassInstanceArgs(ReflectionClass $reflectionClass, array $args): object` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:656`
  - `public function newClassInstanceWithoutConstructor(ReflectionClass $reflectionClass): object` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:667`
  - `public function newClassLazyGhost(ReflectionClass $reflectionClass, ?callable $initializer = null): object` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:679`
  - `public function newClassLazyProxy(ReflectionClass $reflectionClass, callable $factory): object` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:691`
  - `public function resetClassAsLazyGhost(ReflectionClass $reflectionClass, object $object, ?callable $initializer = null): void` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:704`
  - `public function resetClassAsLazyProxy(ReflectionClass $reflectionClass, object $object, callable $factory): void` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:718`
  - `public function setClassStaticPropertyValue(ReflectionClass $reflectionClass, string $propertyName, mixed $value): void` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:732`
  - `public function classToString(ReflectionClass $reflectionClass): string` at `App/Utilities/Traits/Reflection/ReflectionClassTrait.php:743`

### `ReflectionConstantTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionConstantTrait`
- Type: `method provider`
- Summary: `Covers ReflectionConstant & ReflectionClassConstant methods.`
- Imports: `ReflectionConstant`, `ReflectionClassConstant`, `ReflectionExtension`, `ReflectionType`, `ReflectionClass`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `26` total (`26` public, `0` protected, `0` private)
  - `public function createConstant(object|string $class, string $name): ReflectionConstant` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:29`
  - `public function getConstantExtension(ReflectionConstant $constant): ?ReflectionExtension` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:40`
  - `public function getConstantExtensionName(ReflectionConstant $constant): string|false` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:51`
  - `public function getConstantFileName(ReflectionConstant $constant): string|false` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:62`
  - `public function getConstantName(ReflectionConstant $constant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:73`
  - `public function getConstantNamespaceName(ReflectionConstant $constant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:84`
  - `public function getConstantShortName(ReflectionConstant $constant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:95`
  - `public function getConstantValue(ReflectionConstant $constant): mixed` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:106`
  - `public function isConstantDeprecated(ReflectionConstant $constant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:117`
  - `public function constantToString(ReflectionConstant $constant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:128`
  - `public function createClassConstant(string|object $class, string $constantName): ReflectionClassConstant` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:142`
  - `public function getClassConstantAttributes(ReflectionClassConstant $classConstant, ?string $name = null, int $flags = 0): array` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:155`
  - `public function getClassConstantDeclaringClass(ReflectionClassConstant $classConstant): ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:169`
  - `public function getClassConstantDocComment(ReflectionClassConstant $classConstant): string|false` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:180`
  - `public function getClassConstantModifiers(ReflectionClassConstant $classConstant): int` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:191`
  - `public function getClassConstantName(ReflectionClassConstant $classConstant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:202`
  - `public function getClassConstantType(ReflectionClassConstant $classConstant): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:213`
  - `public function getClassConstantValue(ReflectionClassConstant $classConstant): mixed` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:224`
  - `public function hasClassConstantType(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:235`
  - `public function isClassConstantDeprecated(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:246`
  - `public function isClassConstantEnumCase(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:257`
  - `public function isClassConstantFinal(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:268`
  - `public function isClassConstantPrivate(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:279`
  - `public function isClassConstantProtected(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:290`
  - `public function isClassConstantPublic(ReflectionClassConstant $classConstant): bool` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:301`
  - `public function classConstantToString(ReflectionClassConstant $classConstant): string` at `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php:312`

### `ReflectionEnumTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionEnumTrait`
- Type: `method provider`
- Summary: `Covers ReflectionEnum, ReflectionEnumBackedCase, ReflectionEnumUnitCase.`
- Imports: `ReflectionEnum`, `ReflectionEnumBackedCase`, `ReflectionEnumUnitCase`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `8` total (`8` public, `0` protected, `0` private)
  - `public function getEnumBackingType(ReflectionEnum $reflectionEnum): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:27`
  - `public function getEnumCase(ReflectionEnum $reflectionEnum, string $caseName): ?ReflectionEnumUnitCase` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:39`
  - `public function getEnumCases(ReflectionEnum $reflectionEnum): array` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:50`
  - `public function enumHasCase(ReflectionEnum $reflectionEnum, string $caseName): bool` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:62`
  - `public function isEnumBacked(ReflectionEnum $reflectionEnum): bool` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:73`
  - `public function getEnumBackingValue(ReflectionEnumBackedCase $enumBackedCase): mixed` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:86`
  - `public function getEnumFromUnitCase(ReflectionEnumUnitCase $enumUnitCase): ReflectionEnum` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:99`
  - `public function getEnumUnitCaseValue(ReflectionEnumUnitCase $enumUnitCase): mixed` at `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php:110`

### `ReflectionExtensionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionExtensionTrait`
- Type: `method provider`
- Summary: `Covers ReflectionExtension methods.`
- Imports: `ReflectionExtension`, `ReflectionClass`, `ReflectionFunction`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `12` total (`12` public, `0` protected, `0` private)
  - `public function getExtensionClasses(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:27`
  - `public function getExtensionClassNames(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:38`
  - `public function getExtensionConstants(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:49`
  - `public function getExtensionDependencies(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:60`
  - `public function getExtensionFunctions(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:71`
  - `public function getExtensionINIEntries(ReflectionExtension $extension): array` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:82`
  - `public function getExtensionName(ReflectionExtension $extension): string` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:93`
  - `public function getExtensionVersion(ReflectionExtension $extension): ?string` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:104`
  - `public function printExtensionInfo(ReflectionExtension $extension): void` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:116`
  - `public function isExtensionPersistent(ReflectionExtension $extension): bool` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:127`
  - `public function isExtensionTemporary(ReflectionExtension $extension): bool` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:138`
  - `public function extensionToString(ReflectionExtension $extension): string` at `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php:149`

### `ReflectionFunctionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionFunctionTrait`
- Type: `method provider`
- Summary: `Covers ReflectionFunction, ReflectionFunctionAbstract.`
- Imports: `Closure`, `ReflectionFunction`, `ReflectionFunctionAbstract`, `ReflectionClass`, `ReflectionExtension`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `38` total (`38` public, `0` protected, `0` private)
  - `public function getFunctionClosure(ReflectionFunction $reflectionFunction): Closure` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:29`
  - `public function invokeFunction(ReflectionFunction $reflectionFunction, mixed ...$args): mixed` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:41`
  - `public function invokeFunctionArgs(ReflectionFunction $reflectionFunction, array $args): mixed` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:53`
  - `public function isFunctionAnonymous(ReflectionFunction $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:64`
  - `public function isFunctionDisabled(ReflectionFunction $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:75`
  - `public function reflectionFunctionToString(ReflectionFunction $reflectionFunction): string` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:86`
  - `public function getFunctionAttributes(ReflectionFunctionAbstract $reflectionFunction, ?string $name = null, int $flags = 0): array` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:101`
  - `public function getClosureCalledClass(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:112`
  - `public function getClosureScopeClass(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:123`
  - `public function getClosureThis(ReflectionFunctionAbstract $reflectionFunction): ?object` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:134`
  - `public function getClosureUsedVariables(ReflectionFunctionAbstract $reflectionFunction): array` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:145`
  - `public function getFunctionDocComment(ReflectionFunctionAbstract $reflectionFunction): string|false` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:156`
  - `public function getFunctionEndLine(ReflectionFunctionAbstract $reflectionFunction): int|false` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:167`
  - `public function getFunctionExtension(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionExtension` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:178`
  - `public function getFunctionExtensionName(ReflectionFunctionAbstract $reflectionFunction): string|false` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:189`
  - `public function getFunctionFileName(ReflectionFunctionAbstract $reflectionFunction): string|false` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:200`
  - `public function getFunctionNamespaceName(ReflectionFunctionAbstract $reflectionFunction): string` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:211`
  - `public function getFunctionNumberOfParameters(ReflectionFunctionAbstract $reflectionFunction): int` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:222`
  - `public function getFunctionNumberOfRequiredParameters(ReflectionFunctionAbstract $reflectionFunction): int` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:233`
  - `public function getFunctionParameters(ReflectionFunctionAbstract $reflectionFunction): array` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:244`
  - `public function getFunctionName(ReflectionFunctionAbstract $reflectionFunction): string` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:255`
  - `public function getFunctionReturnType(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:266`
  - `public function getFunctionShortName(ReflectionFunctionAbstract $reflectionFunction): string` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:277`
  - `public function getFunctionStartLine(ReflectionFunctionAbstract $reflectionFunction): int|false` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:288`
  - `public function getFunctionStaticVariables(ReflectionFunctionAbstract $reflectionFunction): array` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:299`
  - `public function getFunctionTentativeReturnType(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:310`
  - `public function functionHasReturnType(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:321`
  - `public function functionHasTentativeReturnType(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:332`
  - `public function isFunctionInNamespace(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:343`
  - `public function isFunctionClosure(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:354`
  - `public function isFunctionDeprecated(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:365`
  - `public function isFunctionGenerator(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:376`
  - `public function isFunctionInternal(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:387`
  - `public function isFunctionStatic(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:398`
  - `public function isFunctionUserDefined(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:409`
  - `public function isFunctionVariadic(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:420`
  - `public function functionReturnsReference(ReflectionFunctionAbstract $reflectionFunction): bool` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:431`
  - `public function functionToString(ReflectionFunctionAbstract $reflectionFunction): string` at `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php:442`

### `ReflectionGeneratorTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionGeneratorTrait`
- Type: `method provider`
- Summary: `Covers ReflectionGenerator methods.`
- Imports: `ReflectionGenerator`, `Generator`, `ReflectionFunctionAbstract`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `7` total (`7` public, `0` protected, `0` private)
  - `public function getGeneratorExecutingFile(ReflectionGenerator $reflectionGenerator): string` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:26`
  - `public function getExecutingGenerator(ReflectionGenerator $reflectionGenerator): Generator` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:37`
  - `public function getGeneratorExecutingLine(ReflectionGenerator $reflectionGenerator): int` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:48`
  - `public function getGeneratorFunction(ReflectionGenerator $reflectionGenerator): ReflectionFunctionAbstract` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:59`
  - `public function getGeneratorThis(ReflectionGenerator $reflectionGenerator): ?object` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:70`
  - `public function getGeneratorTrace(ReflectionGenerator $reflectionGenerator): array` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:81`
  - `public function isGeneratorClosed(ReflectionGenerator $reflectionGenerator): bool` at `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php:92`

### `ReflectionMethodTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionMethodTrait`
- Type: `method provider`
- Summary: `Covers ReflectionMethod methods.`
- Imports: `ReflectionMethod`, `ReflectionClass`, `Closure;`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `16` total (`16` public, `0` protected, `0` private)
  - `public function getMethodClosure(ReflectionMethod $reflectionMethod, ?object $object = null): Closure` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:27`
  - `public function getMethodDeclaringClass(ReflectionMethod $reflectionMethod): ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:38`
  - `public function getMethodModifiers(ReflectionMethod $reflectionMethod): int` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:49`
  - `public function getMethodPrototype(ReflectionMethod $reflectionMethod): ReflectionMethod` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:60`
  - `public function methodHasPrototype(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:71`
  - `public function invokeMethod(ReflectionMethod $reflectionMethod, ?object $object = null, mixed ...$args): mixed` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:84`
  - `public function invokeMethodArgs(ReflectionMethod $reflectionMethod, array $args, ?object $object = null): mixed` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:97`
  - `public function isMethodAbstract(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:108`
  - `public function isConstructorMethod(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:119`
  - `public function isDestructorMethod(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:130`
  - `public function isMethodFinal(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:141`
  - `public function isMethodPrivate(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:152`
  - `public function isMethodProtected(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:163`
  - `public function isMethodPublic(ReflectionMethod $reflectionMethod): bool` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:174`
  - `public function setMethodAccessible(ReflectionMethod $reflectionMethod, bool $accessible): void` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:186`
  - `public function methodToString(ReflectionMethod $reflectionMethod): string` at `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php:197`

### `ReflectionParameterTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionParameterTrait`
- Type: `method provider`
- Summary: `Covers  ReflectionParameter.`
- Imports: `ReflectionParameter`, `ReflectionClass`, `ReflectionFunctionAbstract`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `18` total (`18` public, `0` protected, `0` private)
  - `public function canParameterBeNull(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:27`
  - `public function canParameterBePassedByValue(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:38`
  - `public function getParameterAttributes(ReflectionParameter $parameter, ?string $name = null, int $flags = 0): array` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:51`
  - `public function getParameterDeclaringClass(ReflectionParameter $parameter): ?ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:62`
  - `public function getParameterDeclaringFunction(ReflectionParameter $parameter): ReflectionFunctionAbstract` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:73`
  - `public function getParameterDefaultValue(ReflectionParameter $parameter): mixed` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:84`
  - `public function getParameterDefaultValueConstantName(ReflectionParameter $parameter): ?string` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:95`
  - `public function getParameterName(ReflectionParameter $parameter): string` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:106`
  - `public function getParameterPosition(ReflectionParameter $parameter): int` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:117`
  - `public function getParameterType(ReflectionParameter $parameter): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:128`
  - `public function hasParameterType(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:139`
  - `public function isParameterDefaultValueAvailable(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:150`
  - `public function isParameterDefaultValueConstant(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:161`
  - `public function isParameterOptional(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:172`
  - `public function isParameterPassedByReference(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:183`
  - `public function isParameterPromoted(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:194`
  - `public function isParameterVariadic(ReflectionParameter $parameter): bool` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:205`
  - `public function parameterToString(ReflectionParameter $parameter): string` at `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php:216`

### `ReflectionPropertyTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionPropertyTrait`
- Type: `method provider`
- Summary: `Covers ReflectionProperty and ReflectionParameter.`
- Imports: `ReflectionProperty`, `ReflectionClass`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `25` total (`25` public, `0` protected, `0` private)
  - `public function getPropertyDeclaringClass(ReflectionProperty $property): ReflectionClass` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:27`
  - `public function getPropertyDefaultValue(ReflectionProperty $property): mixed` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:38`
  - `public function getPropertyDocComment(ReflectionProperty $property): string|false` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:49`
  - `public function getPropertyModifiers(ReflectionProperty $property): int` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:60`
  - `public function getPropertyName(ReflectionProperty $property): string` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:71`
  - `public function getPropertyType(ReflectionProperty $property): ?ReflectionType` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:82`
  - `public function getPropertyValue(ReflectionProperty $property, ?object $object = null): mixed` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:94`
  - `public function hasPropertyDefaultValue(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:105`
  - `public function hasPropertyType(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:116`
  - `public function isPropertyDefault(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:127`
  - `public function isPropertyDynamic(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:138`
  - `public function isPropertyInitialized(ReflectionProperty $property, ?object $object = null): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:150`
  - `public function isPropertyLazy(ReflectionProperty $property, object $object): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:162`
  - `public function isPropertyPrivate(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:173`
  - `public function isPropertyPromoted(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:184`
  - `public function isPropertyProtected(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:195`
  - `public function isPropertyPublic(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:206`
  - `public function isPropertyReadOnly(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:217`
  - `public function isPropertyStatic(ReflectionProperty $property): bool` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:228`
  - `public function setPropertyAccessible(ReflectionProperty $property, bool $accessible): void` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:240`
  - `public function setRawPropertyValueWithoutLazyInitialization(ReflectionProperty $property, object $object, mixed $value): void` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:253`
  - `public function setPropertyValue(ReflectionProperty $property, object $object, mixed $value): void` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:269`
  - `public function skipPropertyLazyInitialization(ReflectionProperty $property, object $object): void` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:281`
  - `public function propertyToString(ReflectionProperty $reflectionProperty): string` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:292`
  - `public function getPropertyAttributes(ReflectionProperty $reflectionProperty, ?string $name = null, int $flags = 0): array` at `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php:305`

### `ReflectionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionTrait`
- Type: `method provider`
- Summary: `Covers ReflectionReference, ReflectionFiber, ReflectionException,`
- Imports: `Reflector`, `ReflectionException`, `ReflectionFiber`, `ReflectionReference`, `Throwable`, `Fiber`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `17` total (`17` public, `0` protected, `0` private)
  - `public function createException(string $message = "", int $code = 0, ?Throwable $previous = null): ReflectionException` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:32`
  - `public function getExceptionMessage(ReflectionException $exception): string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:46`
  - `public function getExceptionPrevious(ReflectionException $exception): ?Throwable` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:57`
  - `public function getExceptionCode(ReflectionException $exception): int` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:68`
  - `public function getExceptionFile(ReflectionException $exception): string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:79`
  - `public function getExceptionLine(ReflectionException $exception): int` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:90`
  - `public function getExceptionTrace(ReflectionException $exception): array` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:101`
  - `public function getExceptionTraceAsString(ReflectionException $exception): string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:112`
  - `public function exceptionToString(ReflectionException $exception): string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:123`
  - `public function createFiber(Fiber $fiber): ReflectionFiber` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:136`
  - `public function getFiberCallable(ReflectionFiber $reflectionFiber): callable` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:147`
  - `public function getFiberExecutingFile(ReflectionFiber $reflectionFiber): ?string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:158`
  - `public function getFiberExecutingLine(ReflectionFiber $reflectionFiber): ?int` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:169`
  - `public function getFiberInstance(ReflectionFiber $reflectionFiber): Fiber` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:180`
  - `public function getFiberTrace(ReflectionFiber $reflectionFiber, int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:192`
  - `public function createReference(array $array, int|string $key): ?ReflectionReference` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:206`
  - `public function getReferenceId(ReflectionReference $reflectionReference): string` at `App/Utilities/Traits/Reflection/ReflectionTrait.php:217`

### `ReflectionTypeTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php`
- FQCN: `App\Utilities\Traits\Reflection\ReflectionTypeTrait`
- Type: `method provider`
- Summary: `Covers ReflectionType, ReflectionNamedType, ReflectionUnionType, ReflectionIntersectionType.`
- Imports: `ReflectionType`, `ReflectionNamedType`, `ReflectionUnionType`, `ReflectionIntersectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Methods: `6` total (`6` public, `0` protected, `0` private)
  - `public function getTypeName(ReflectionNamedType $type): string` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:27`
  - `public function isBuiltinType(ReflectionNamedType $type): bool` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:38`
  - `public function canTypeBeNull(ReflectionType $type): bool` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:50`
  - `public function typeToString(ReflectionType $type): string` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:61`
  - `public function getIntersectionTypes(ReflectionIntersectionType $type): array` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:72`
  - `public function getUnionTypes(ReflectionUnionType $type): array` at `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php:85`

## Rules

### `RuleTrait`

- Path: `App/Utilities/Traits/Rules/RuleTrait.php`
- FQCN: `App\Utilities\Traits\Rules\RuleTrait`
- Type: `method provider`
- Summary: `Provides utility methods for validating input values against common rules.`
- Imports: `App\Utilities\Traits\ArrayTrait`, `App\Utilities\Traits\CheckerTrait`, `App\Utilities\Traits\ConversionTrait`, `App\Utilities\Traits\EncodingTrait`, `App\Utilities\Traits\ManipulationTrait`, `App\Utilities\Traits\TypeCheckerTrait`
- Composed traits: `ArrayTrait`, `CheckerTrait`, `ConversionTrait`, `EncodingTrait`, `ManipulationTrait`, `TypeCheckerTrait`
- Current consumers: `App/Utilities/Sanitation/PatternSanitizer.php`, `App/Utilities/Validation/GeneralValidator.php`, `App/Utilities/Validation/PatternValidator.php`
- Methods: `27` total (`27` public, `0` protected, `0` private)
  - `public function ruleRequire(mixed $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:31`
  - `public function ruleMin(float|int $input, float|int $min): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:43`
  - `public function ruleMax(float|int $input, float|int $max): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:55`
  - `public function ruleBetween(float|int $input, float|int $min, float|int $max): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:68`
  - `public function ruleLess(float|int $input, float|int $threshold): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:80`
  - `public function ruleGreater(float|int $input, float|int $threshold): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:92`
  - `public function ruleMinLength(string $input, int $min): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:104`
  - `public function ruleMaxLength(string $input, int $max): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:116`
  - `public function ruleLengthBetween(string $input, int $min, int $max): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:129`
  - `public function ruleInArray(mixed $input, array $array): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:143`
  - `public function ruleNotInArray(mixed $input, array $array): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:155`
  - `public function ruleIsInt(mixed $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:166`
  - `public function ruleIsFloat(mixed $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:177`
  - `public function ruleIsString(mixed $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:188`
  - `public function ruleIsBoolean(mixed $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:199`
  - `public function ruleIsAssociativeArray(array $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:210`
  - `public function ruleArrayUnique(array $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:221`
  - `public function ruleDivisibleBy(int $input, int $divisor): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:243`
  - `public function ruleNotEmpty(string $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:254`
  - `public function ruleStep(float|int $input, float|int $step, float|int $base = 0): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:267`
  - `public function ruleArraySize(array $input, int $min, int $max): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:286`
  - `public function ruleSequential(array $numbers, bool $allowGaps = false): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:300`
  - `public function ruleStartsWith(string $input, string $prefix): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:356`
  - `public function ruleEndsWith(string $input, string $suffix): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:368`
  - `public function rulePositive(float|int $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:379`
  - `public function ruleNegative(float|int $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:390`
  - `public function ruleArrayNotEmpty(array $input): bool` at `App/Utilities/Traits/Rules/RuleTrait.php:401`

### `RulesTrait`

- Path: `App/Utilities/Traits/Rules/RulesTrait.php`
- FQCN: `App\Utilities\Traits\Rules\RulesTrait`
- Type: `wrapper trait`
- Composed traits: `RuleTrait`
- Current consumers: `App/Utilities/Sanitation/GeneralSanitizer.php`
- Methods: none declared directly

## Sort

### `DirectorySortTrait`

- Path: `App/Utilities/Traits/Sort/DirectorySortTrait.php`
- FQCN: `App\Utilities\Traits\Sort\DirectorySortTrait`
- Type: `method provider`
- Summary: `Provides utility methods for sorting directories based on various criteria.`
- Imports: `App\Utilities\Traits\TypeCheckerTrait`
- Composed traits: `TypeCheckerTrait`
- Current consumers: none found outside the traits layer
- Methods: `9` total (`0` public, `8` protected, `1` private)
  - `protected function sortByName($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:26`
  - `protected function sortByPath($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:38`
  - `protected function sortByModifiedTime($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:50`
  - `protected function sortByAccessedTime($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:62`
  - `protected function sortByCreationTime($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:74`
  - `protected function sortByPermissions($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:86`
  - `protected function sortByOwner($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:98`
  - `protected function sortByGroup($a, $b): int` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:110`
  - `private function resolveSortableDirectoryPath($fileInfo): string` at `App/Utilities/Traits/Sort/DirectorySortTrait.php:115`

### `FileSortTrait`

- Path: `App/Utilities/Traits/Sort/FileSortTrait.php`
- FQCN: `App\Utilities\Traits\Sort\FileSortTrait`
- Type: `method provider`
- Summary: `Provides utility methods for sorting files based on various properties.`
- Imports: `App\Utilities\Traits\TypeCheckerTrait`
- Composed traits: `TypeCheckerTrait`
- Current consumers: none found outside the traits layer
- Methods: `11` total (`0` public, `10` protected, `1` private)
  - `protected function sortByName($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:35`
  - `protected function sortByPath($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:47`
  - `protected function sortBySize($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:59`
  - `protected function sortByExtension($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:71`
  - `protected function sortByModifiedTime($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:83`
  - `protected function sortByAccessedTime($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:95`
  - `protected function sortByCreationTime($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:107`
  - `protected function sortByPermissions($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:119`
  - `protected function sortByOwner($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:131`
  - `protected function sortByGroup($a, $b): int` at `App/Utilities/Traits/Sort/FileSortTrait.php:143`
  - `private function resolveSortableFilePath($fileInfo): string` at `App/Utilities/Traits/Sort/FileSortTrait.php:148`
