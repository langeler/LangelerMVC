# Utilities Traits Overview

This document is generated from the current code in `App/Utilities/Traits` and is intended to be a grounded reference for future framework work.

For the full method-by-method catalog, see `Docs/UtilitiesTraitsReference.md`.
To regenerate that catalog from source, run `perl Scripts/GenerateUtilitiesTraitsReference.pl . > Docs/UtilitiesTraitsReference.md`.

## Snapshot

- Trait files: `50`
- Traits with declared methods: `42`
- Wrapper traits that only re-export other traits: `8`
- Traits with their own `__construct()`: `7`

## Reading Notes

- `Imports` lists top-level PHP imports in the file.
- `Composed traits` lists traits mixed into the current trait body via `use ...;` inside the trait.
- `Current consumers` is based on actual trait composition in concrete classes and traits outside `App/Utilities/Traits`.

## Immediate Leverage Notes

- Prefer the root aliases in `App\Utilities\Traits` when they exist. They are the convenience surface currently used by the framework, while deeper traits live under sub-namespaces such as `Criteria`, `Sort`, `Filters`, `Patterns`, `Query`, `Reflection`, and `Rules`.
- Traits with their own constructors require explicit care when composed together. Current constructor traits: `DateTimeTrait`, `IteratorTrait`, `RecursiveIteratorTrait`, `SanitationFilterTrait`, `SanitationPatternTrait`, `ValidationFilterTrait`, `ValidationPatternTrait`.
- Protected-only traits are intended as internal behavior for finders and similar framework internals, not as public helper surfaces. Current protected-only traits: `ApplicationPathTrait`, `DirectoryCriteriaTrait`, `DirectorySortTrait`, `FileCriteriaTrait`, `FileSortTrait`.
- Method collisions already exist. If multiple traits are mixed into a class, aliasing or `insteadof` conflict resolution may be required.
- `DataQueryTrait` and `SchemaQueryTrait` are now stable framework entry surfaces through `App\Utilities\Query\DataQuery` and `App\Utilities\Query\SchemaQuery`. Prefer those wrappers over composing query traits directly in higher-level classes.

## Practical Leverage Map

- `ErrorTrait` is the framework-standard error boundary. It is already the dominant cross-cutting trait across core, HTTP, presentation, handlers, and managers, so new framework classes should generally align with it instead of inventing local error wrappers.
- `ArrayTrait`, `TypeCheckerTrait`, and `ExistenceCheckerTrait` form the main low-level helper surface already shared by the cache, database, request, validation, module, and settings layers.
- `SanitationTrait` and `ValidationTrait` are the general filter-driven input entrypoints. `SanitationPatternTrait` and `ValidationPatternTrait` are the stricter regex-driven entrypoints for well-defined text formats.
- `RuleTrait` and `RulesTrait` are additive constraint layers. They are intended to sit beside sanitizer and validator traits rather than replace them.
- `DirectoryCriteriaTrait` with `DirectorySortTrait`, and `FileCriteriaTrait` with `FileSortTrait`, are finder-only surfaces. They fit best inside classes that already inherit the `Finder` lifecycle and data shape.
- `DataQueryTrait` and `SchemaQueryTrait` are surfaced through `App/Utilities/Query/DataQuery.php` and `App/Utilities/Query/SchemaQuery.php`. They should generally be consumed through those wrapper classes so SQL normalization, bindings, and driver handling stay centralized.
- The `Reflection*Trait` family is already centralized behind `App/Utilities/Managers/System/ReflectionManager.php`, which is the cleanest entrypoint for reflection work.
- `DateTimeTrait` is currently surfaced through `DateTimeManager`. `HashingTrait`, `LocaleUtilityTrait`, `RetrieverTrait`, `IteratorTrait`, and `RecursiveIteratorTrait` are available but not yet broadly integrated into higher-level framework flows.

## Current Trait Entry Points

- General sanitization: `App/Utilities/Sanitation/GeneralSanitizer.php`
- Pattern sanitization: `App/Utilities/Sanitation/PatternSanitizer.php`
- General validation: `App/Utilities/Validation/GeneralValidator.php`
- Pattern validation: `App/Utilities/Validation/PatternValidator.php`
- Directory search/filter/sort: `App/Utilities/Finders/DirectoryFinder.php`
- File search/filter/sort: `App/Utilities/Finders/FileFinder.php`
- Query composition: `App/Utilities/Query/DataQuery.php`, `App/Utilities/Query/SchemaQuery.php`
- Date/time utilities: `App/Utilities/Managers/System/DateTimeManager.php`
- Reflection utilities: `App/Utilities/Managers/System/ReflectionManager.php`

## Wrapper Traits

- `DirectoryCriteriaTrait` re-exports: `Criteria\DirectoryCriteriaTrait`
- `DirectorySortTrait` re-exports: `Sort\DirectorySortTrait`
- `FileCriteriaTrait` re-exports: `Criteria\FileCriteriaTrait`
- `FileSortTrait` re-exports: `Sort\FileSortTrait`
- `LocaleTrait` re-exports: `LocaleUtilityTrait`
- `RulesTrait` re-exports: `RuleTrait`
- `SanitationTrait` re-exports: `SanitationFilterTrait`
- `ValidationTrait` re-exports: `ValidationFilterTrait`

## Method Collisions

- `__construct`: `DateTimeTrait`, `IteratorTrait`, `RecursiveIteratorTrait`, `SanitationFilterTrait`, `SanitationPatternTrait`, `ValidationFilterTrait`, `ValidationPatternTrait`
- `all`: `ArrayTrait`, `DataQueryTrait`
- `any`: `ArrayTrait`, `DataQueryTrait`
- `filter`: `ArrayTrait`, `DataQueryTrait`
- `filterByAccessedTime`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByCreationTime`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByDepth`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByExecutable`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByGroup`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByModifiedTime`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByName`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByOwner`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByPath`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByPatternName`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByPatternPath`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByPermissions`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByReadable`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterBySymlink`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByType`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `filterByWritable`: `DirectoryCriteriaTrait`, `FileCriteriaTrait`
- `getClassMethods`: `ReflectionClassTrait`, `RetrieverTrait`
- `getFilterOptions`: `SanitationFilterTrait`, `ValidationFilterTrait`
- `intersect`: `ArrayTrait`, `DataQueryTrait`
- `isNull`: `DataQueryTrait`, `TypeCheckerTrait`
- `isNumeric`: `CheckerTrait`, `TypeCheckerTrait`
- `join`: `DataQueryTrait`, `ManipulationTrait`
- `pad`: `ArrayTrait`, `ManipulationTrait`
- `repeat`: `LoopTrait`, `ManipulationTrait`
- `replace`: `ArrayTrait`, `ManipulationTrait`, `PatternTrait`
- `reverse`: `ArrayTrait`, `ManipulationTrait`
- `sanitizeFloat`: `SanitationFilterTrait`, `SanitationPatternTrait`
- `sanitizeUrl`: `SanitationFilterTrait`, `SanitationPatternTrait`
- `shuffle`: `ArrayTrait`, `ManipulationTrait`
- `sortByAccessedTime`: `DirectorySortTrait`, `FileSortTrait`
- `sortByCreationTime`: `DirectorySortTrait`, `FileSortTrait`
- `sortByGroup`: `DirectorySortTrait`, `FileSortTrait`
- `sortByModifiedTime`: `DirectorySortTrait`, `FileSortTrait`
- `sortByName`: `DirectorySortTrait`, `FileSortTrait`
- `sortByOwner`: `DirectorySortTrait`, `FileSortTrait`
- `sortByPath`: `DirectorySortTrait`, `FileSortTrait`
- `sortByPermissions`: `DirectorySortTrait`, `FileSortTrait`
- `soundsLike`: `DataQueryTrait`, `MetricsTrait`
- `split`: `ManipulationTrait`, `PatternTrait`
- `validateFloat`: `ValidationFilterTrait`, `ValidationPatternTrait`
- `validateInt`: `ValidationFilterTrait`, `ValidationPatternTrait`
- `validateUrl`: `ValidationFilterTrait`, `ValidationPatternTrait`

## Trait Catalog

### Core Traits

#### `ApplicationPathTrait`

- Path: `App/Utilities/Traits/ApplicationPathTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Data/Finder.php`, `App/Core/Session.php`
- Protected methods: `frameworkBasePath`, `frameworkStoragePath`

#### `ArrayTrait`

- Path: `App/Utilities/Traits/ArrayTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Http/Request.php`, `App/Abstracts/Http/Response.php`, `App/Abstracts/Presentation/Presenter.php`, `App/Core/Container.php`, `App/Core/Database.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/System/ErrorManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Public methods: `changeKeyCase`, `chunk`, `column`, `combine`, `diff`, `diffAssoc`, `diffKey`, `fill`, `fillKeys`, `filter`, `flip`, `intersect`, `intersectAssoc`, `intersectKey`, `map`, `create`, `assign`, `all`, `any`, `current`, `end`, `key`, `next`, `pos`, `prev`, `reset`, `find`, `findKey`, `keyFirst`, `keyLast`, `extract`, `diffUKey`, `intersectUKey`, `uDiffUAssoc`, `uIntersectUAssoc`, `keyExists`, `getKeys`, `getValues`, `search`, `flatten`, `countValues`, `uDiff`, `uDiffAssoc`, `uIntersectAssoc`, `merge`, `mergeRecursive`, `multisort`, `pad`, `replace`, `replaceRecursive`, `walk`, `diffUAssoc`, `intersectUAssoc`, `uIntersect`, `natcasesort`, `natsort`, `sizeOf`, `rand`, `reverse`, `pop`, `push`, `shift`, `unshift`, `slice`, `splice`, `product`, `sum`, `unique`, `walkRecursive`, `isList`, `arraykeyExists`, `differenceByKeys`, `replaceElements`, `filterNonEmpty`, `diffKeyRecursive`, `reduce`, `shuffle`, `sortRecursive`, `mergeUnique`, `partition`

#### `CheckerTrait`

- Path: `App/Utilities/Traits/CheckerTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Database/Query.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Public methods: `isAlphanumeric`, `isAlphabetic`, `isNumeric`, `isLowercase`, `isUppercase`, `isWhitespace`, `contains`, `startsWith`, `endsWith`, `isJson`, `isHexadecimal`

#### `ConversionTrait`

- Path: `App/Utilities/Traits/ConversionTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`
- Public methods: `toBool`, `toFloat`, `toInt`, `changeType`, `toString`, `toJson`, `fromJson`, `toDateTime`, `fromDateTime`, `serializeData`, `unserializeData`, `binToHex`, `hexToBin`, `stringToArray`, `arrayToString`

#### `DateTimeTrait`

- Path: `App/Utilities/Traits/DateTimeTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Utilities/Managers/System/DateTimeManager.php`
- Public methods: `__construct`, `isValidDate`, `formatTimestamp`, `getDefaultTimezone`, `setDefaultTimezone`, `parseDate`, `parseDateFromFormat`, `getSunInfo`, `getSunrise`, `getSunset`, `getDateInfo`, `getMicroTime`, `getCurrentTimestamp`, `parseToTimestamp`, `formatGmtDate`, `getLocalTime`, `listTimeZoneAbbreviations`, `listTimeZoneIdentifiers`, `getTimeZoneNameFromAbbr`, `getTimeZoneDbVersion`

#### `DirectoryCriteriaTrait`

- Path: `App/Utilities/Traits/DirectoryCriteriaTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: wrapper trait
- Composed traits: `Criteria\DirectoryCriteriaTrait`
- Current consumers: `App/Utilities/Finders/DirectoryFinder.php`

#### `DirectorySortTrait`

- Path: `App/Utilities/Traits/DirectorySortTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: wrapper trait
- Composed traits: `Sort\DirectorySortTrait`
- Current consumers: `App/Utilities/Finders/DirectoryFinder.php`

#### `EncodingTrait`

- Path: `App/Utilities/Traits/EncodingTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Database/Query.php`
- Public methods: `addSlashesToString`, `stripSlashesFromString`, `base64EncodeString`, `base64DecodeString`, `encodeStringForUrl`, `decodeStringFromUrl`, `encodeStringForRawUrl`, `decodeStringFromRawUrl`, `encodeHtmlEntitiesString`, `encodeSpecialCharsString`, `decodeHtmlEntitiesString`, `quotedPrintableEncodeString`, `quotedPrintableDecodeString`, `uuencodeString`, `uudecodeString`, `isValidEncoding`, `convertStringCase`, `convertStringEncoding`, `detectStringEncoding`, `setInternalStringEncoding`, `listSupportedEncodings`, `getStringLength`, `findSubstringInString`, `findLastSubstringInString`, `convertStringToLower`, `convertStringToUpper`, `getSubstringOfString`

#### `ErrorTrait`

- Path: `App/Utilities/Traits/ErrorTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Imports: `Throwable`, `UnexpectedValueException`
- Composed traits: `ExistenceCheckerTrait`, `TypeCheckerTrait`
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Database/Query.php`, `App/Abstracts/Http/Controller.php`, `App/Abstracts/Http/Middleware.php`, `App/Abstracts/Http/Service.php`, `App/Abstracts/Presentation/View.php`, `App/Core/App.php`, `App/Core/Config.php`, `App/Core/Database.php`, `App/Core/Router.php`, `App/Core/Session.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Handlers/NumberFormatterHandler.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`, `App/Utilities/Managers/System/SettingsManager.php`
- Public methods: `wrapInTry`

#### `ExistenceCheckerTrait`

- Path: `App/Utilities/Traits/ExistenceCheckerTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Sanitizer.php`, `App/Abstracts/Data/Validator.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`
- Public methods: `classExists`, `interfaceExists`, `traitExists`, `methodExists`, `propertyExists`, `constantExists`, `functionExists`

#### `FileCriteriaTrait`

- Path: `App/Utilities/Traits/FileCriteriaTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: wrapper trait
- Composed traits: `Criteria\FileCriteriaTrait`
- Current consumers: `App/Utilities/Finders/FileFinder.php`

#### `FileSortTrait`

- Path: `App/Utilities/Traits/FileSortTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: wrapper trait
- Composed traits: `Sort\FileSortTrait`
- Current consumers: `App/Utilities/Finders/FileFinder.php`

#### `HashingTrait`

- Path: `App/Utilities/Traits/HashingTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: none found outside the traits layer
- Public methods: `hash`, `hmac`, `pbkdf2`, `passwordHash`, `verifyPassword`, `compare`, `getAvailableAlgorithms`, `hashFile`, `hmacFile`, `getHashState`, `computeRollingHash`

#### `LocaleTrait`

- Path: `App/Utilities/Traits/LocaleTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: wrapper trait
- Composed traits: `LocaleUtilityTrait`
- Current consumers: none found outside the traits layer

#### `LocaleUtilityTrait`

- Path: `App/Utilities/Traits/LocaleUtilityTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: none found outside the traits layer
- Public methods: `setLocale`, `getLocaleSettings`, `localeCompare`, `localeSort`, `localeCaseConvert`

#### `LoopTrait`

- Path: `App/Utilities/Traits/LoopTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`
- Public methods: `each`, `count`, `until`, `atLeastOnce`, `repeat`, `through`, `stepRange`

#### `ManipulationTrait`

- Path: `App/Utilities/Traits/ManipulationTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`, `App/Abstracts/Database/Query.php`
- Public methods: `split`, `join`, `pad`, `replace`, `findIgnoreCase`, `findFirst`, `findLast`, `findSubstring`, `compareIgnoreCase`, `length`, `substring`, `splitToArray`, `toLower`, `toUpper`, `capitalizeWords`, `trim`, `trimLeft`, `trimRight`, `reverse`, `repeat`, `shuffle`, `escapeHtml`, `tokenizeString`

#### `MetricsTrait`

- Path: `App/Utilities/Traits/MetricsTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Cache.php`
- Public methods: `distance`, `similarityScore`, `soundsLike`, `metaphoneMatch`, `jaroWinklerMatch`
- Private methods: `calculateJaroWinkler`

#### `RetrieverTrait`

- Path: `App/Utilities/Traits/RetrieverTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: none found outside the traits layer
- Public methods: `getClass`, `getClassMethods`, `getDeclaredClasses`, `getDeclaredInterfaces`, `getDefinedFunctions`, `getDefinedVars`, `getIncludedFiles`, `getLoadedExtensions`, `getObjectVars`, `getParentClass`, `getResources`

#### `TypeCheckerTrait`

- Path: `App/Utilities/Traits/TypeCheckerTrait.php`
- Namespace: `App\Utilities\Traits`
- Type: method provider
- Current consumers: `App/Abstracts/Data/Sanitizer.php`, `App/Abstracts/Data/Validator.php`, `App/Abstracts/Database/Query.php`, `App/Core/Database.php`, `App/Utilities/Handlers/NamespaceResolveHandler.php`, `App/Utilities/Managers/Data/CacheManager.php`, `App/Utilities/Managers/Data/ModuleManager.php`
- Public methods: `isArray`, `isBool`, `isCallable`, `isCountable`, `isDirectory`, `isFile`, `isFloat`, `isInt`, `isIterable`, `isNull`, `isNumeric`, `isObject`, `isResource`, `isScalar`, `isIntegerOrNull`, `isString`, `isLink`, `isSubclassOf`, `isUploadedFile`, `isWritable`, `isReadable`, `isEmpty`, `isSet`, `isInArray`

### Criteria

#### `DirectoryCriteriaTrait`

- Path: `App/Utilities/Traits/Criteria/DirectoryCriteriaTrait.php`
- Namespace: `App\Utilities\Traits\Criteria`
- Type: method provider
- Current consumers: none found outside the traits layer
- Protected methods: `filterByPath`, `filterByName`, `filterByType`, `filterByOwner`, `filterByGroup`, `filterByPermissions`, `filterByModifiedTime`, `filterByAccessedTime`, `filterByCreationTime`, `filterByDepth`, `filterBySymlink`, `filterByExecutable`, `filterByWritable`, `filterByReadable`, `filterByPatternName`, `filterByPatternPath`

#### `FileCriteriaTrait`

- Path: `App/Utilities/Traits/Criteria/FileCriteriaTrait.php`
- Namespace: `App\Utilities\Traits\Criteria`
- Type: method provider
- Current consumers: none found outside the traits layer
- Protected methods: `filterByPath`, `filterByName`, `filterByExtension`, `filterByType`, `filterBySize`, `filterByPermissions`, `filterByOwner`, `filterByGroup`, `filterByModifiedTime`, `filterByAccessedTime`, `filterByCreationTime`, `filterBySymlink`, `filterByDepth`, `filterByExecutable`, `filterByWritable`, `filterByReadable`, `filterByPatternName`, `filterByPatternExtension`, `filterByPatternPath`

### Filters

#### `FiltrationTrait`

- Path: `App/Utilities/Traits/Filters/FiltrationTrait.php`
- Namespace: `App\Utilities\Traits\Filters`
- Type: method provider
- Current consumers: none found outside the traits layer
- Public methods: `var`, `varArray`, `input`, `inputArray`, `withCallback`

#### `SanitationFilterTrait`

- Path: `App/Utilities/Traits/Filters/SanitationFilterTrait.php`
- Namespace: `App\Utilities\Traits\Filters`
- Type: method provider
- Imports: `App\Utilities\Traits\ArrayTrait`, `InvalidArgumentException`
- Composed traits: `FiltrationTrait`, `ArrayTrait`
- Current consumers: none found outside the traits layer
- Public methods: `__construct`, `sanitizeEncoded`, `sanitizeString`, `sanitizeEmail`, `sanitizeUrl`, `sanitizeInt`, `sanitizeFloat`, `sanitizeAddSlashes`, `sanitizeFullSpecialChars`
- Private methods: `getFilterOptions`

#### `SanitationTrait`

- Path: `App/Utilities/Traits/Filters/SanitationTrait.php`
- Namespace: `App\Utilities\Traits\Filters`
- Type: wrapper trait
- Composed traits: `SanitationFilterTrait`
- Current consumers: `App/Utilities/Sanitation/GeneralSanitizer.php`

#### `ValidationFilterTrait`

- Path: `App/Utilities/Traits/Filters/ValidationFilterTrait.php`
- Namespace: `App\Utilities\Traits\Filters`
- Type: method provider
- Imports: `App\Utilities\Traits\ArrayTrait`, `InvalidArgumentException`
- Composed traits: `FiltrationTrait`, `ArrayTrait`
- Current consumers: none found outside the traits layer
- Public methods: `__construct`, `validateBoolean`, `validateEmail`, `validateFloat`, `validateInt`, `validateIp`, `validateMac`, `validateRegexp`, `validateUrl`, `validateDomain`
- Private methods: `getFilterOptions`

#### `ValidationTrait`

- Path: `App/Utilities/Traits/Filters/ValidationTrait.php`
- Namespace: `App\Utilities\Traits\Filters`
- Type: wrapper trait
- Composed traits: `ValidationFilterTrait`
- Current consumers: `App/Utilities/Validation/GeneralValidator.php`

### Iterator

#### `IteratorTrait`

- Path: `App/Utilities/Traits/Iterator/IteratorTrait.php`
- Namespace: `App\Utilities\Traits\Iterator`
- Type: method provider
- Imports: `AppendIterator`, `ArrayIterator`, `CachingIterator`, `CallbackFilterIterator`, `DirectoryIterator`, `EmptyIterator`, `FilesystemIterator`, `FilterIterator`, `GlobIterator`, `InfiniteIterator`, `IteratorIterator`, `LimitIterator`, `MultipleIterator`, `NoRewindIterator`, `ParentIterator`, `RegexIterator`, `SeekableIterator`
- Current consumers: none found outside the traits layer
- Public methods: `__construct`, `AppendIterator`, `ArrayIterator`, `CachingIterator`, `CallbackFilterIterator`, `DirectoryIterator`, `EmptyIterator`, `FilesystemIterator`, `FilterIterator`, `GlobIterator`, `InfiniteIterator`, `IteratorIterator`, `LimitIterator`, `MultipleIterator`, `NoRewindIterator`, `ParentIterator`, `RegexIterator`, `SeekableIterator`

#### `RecursiveIteratorTrait`

- Path: `App/Utilities/Traits/Iterator/RecursiveIteratorTrait.php`
- Namespace: `App\Utilities\Traits\Iterator`
- Type: method provider
- Imports: `RecursiveArrayIterator`, `RecursiveCachingIterator`, `RecursiveCallbackFilterIterator`, `RecursiveDirectoryIterator`, `RecursiveFilterIterator`, `RecursiveIteratorIterator`, `RecursiveRegexIterator`, `RecursiveTreeIterator`
- Current consumers: none found outside the traits layer
- Public methods: `__construct`, `RecursiveArrayIterator`, `RecursiveCachingIterator`, `RecursiveCallbackFilterIterator`, `RecursiveDirectoryIterator`, `RecursiveFilterIterator`, `RecursiveIteratorIterator`, `RecursiveRegexIterator`, `RecursiveTreeIterator`

### Patterns

#### `PatternTrait`

- Path: `App/Utilities/Traits/Patterns/PatternTrait.php`
- Namespace: `App\Utilities\Traits\Patterns`
- Type: method provider
- Current consumers: none found outside the traits layer
- Public methods: `match`, `matchAll`, `replace`, `replaceCallback`, `split`, `quote`

#### `SanitationPatternTrait`

- Path: `App/Utilities/Traits/Patterns/SanitationPatternTrait.php`
- Namespace: `App\Utilities\Traits\Patterns`
- Type: method provider
- Imports: `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `PatternTrait`
- Current consumers: `App/Utilities/Sanitation/PatternSanitizer.php`
- Public methods: `__construct`, `sanitizeName`, `sanitizeSsn`, `sanitizePhoneUs`, `sanitizePhoneIntl`, `sanitizeZipUs`, `sanitizeZipUk`, `sanitizeHex`, `sanitizeBinary`, `sanitizeOctal`, `sanitizeCreditCard`, `sanitizeIsbn`, `sanitizeCurrencyUsd`, `sanitizeFileName`, `sanitizeDirectory`, `sanitizePathUnix`, `sanitizeFileExt`, `sanitizeSlug`, `sanitizeUrl`, `sanitizeIpv4`, `sanitizeIpv6`, `sanitizeIntPos`, `sanitizeFloat`, `sanitizePercent`, `sanitizeAlpha`, `sanitizeAlphaNum`, `sanitizeHashtag`

#### `ValidationPatternTrait`

- Path: `App/Utilities/Traits/Patterns/ValidationPatternTrait.php`
- Namespace: `App\Utilities\Traits\Patterns`
- Type: method provider
- Imports: `App\Utilities\Traits\Patterns\PatternTrait`
- Composed traits: `PatternTrait`
- Current consumers: `App/Utilities/Validation/PatternValidator.php`
- Public methods: `__construct`, `validateName`, `validateSsn`, `validatePhoneUs`, `validatePhoneIntl`, `validateHexadecimal`, `validateHexOnly`, `validateBinary`, `validateOctal`, `validateCreditCard`, `validateIsbn10`, `validateIban`, `validateBic`, `validateEthereumAddress`, `validateBitcoinAddress`, `validateFileName`, `validateDirectory`, `validatePathUnix`, `validatePathWindows`, `validateFileExt`, `validateImageExt`, `validateAudioExt`, `validateVideoExt`, `validateSlug`, `validateUrl`, `validateUrlPort`, `validateIpv4`, `validateIpv6`, `validateZipUs`, `validateZipUk`, `validateIntPos`, `validateIntNeg`, `validateInt`, `validateFloatPos`, `validateFloatNeg`, `validateFloat`, `validateScientific`, `validateAlpha`, `validateAlphaSpace`, `validateAlphaDash`, `validateAlphaNum`, `validateAlphaNumSpace`, `validateHashtag`, `validateTwitterHandle`

### Query

#### `DataQueryTrait`

- Path: `App/Utilities/Traits/Query/DataQueryTrait.php`
- Namespace: `App\Utilities\Traits\Query`
- Type: method provider
- Current consumers: `App/Utilities/Query/DataQuery.php`
- Public methods: `where`, `having`, `on`, `filter`, `fetch`, `returning`, `with`, `not`, `and`, `or`, `xor`, `andNot`, `orNot`, `all`, `any`, `equal`, `notEqual`, `notEqualAlt`, `nullSafeEqual`, `greaterThan`, `greaterThanOrEqual`, `lessThan`, `lessThanOrEqual`, `isDistinctFrom`, `notDistinctFrom`, `isNull`, `isNotNull`, `in`, `notIn`, `exists`, `notExists`, `except`, `intersect`, `minus`, `union`, `unionAll`, `between`, `notBetween`, `like`, `notLike`, `iLike`, `regexp`, `notRegexp`, `soundsLike`, `similarTo`, `notSimilarTo`, `connectBy`, `startWith`, `connectByPrior`, `prior`, `withRecursive`, `distinctOn`, `overlaps`, `forUpdate`, `forShare`, `join`, `fullOuterJoin`, `leftJoin`, `rightJoin`, `innerJoin`, `crossJoin`, `naturalJoin`, `fullJoin`, `orderBy`, `groupBy`, `groupingSets`, `cube`, `rollup`, `limit`, `offset`, `insert`, `select`, `update`, `delete`

#### `SchemaQueryTrait`

- Path: `App/Utilities/Traits/Query/SchemaQueryTrait.php`
- Namespace: `App\Utilities\Traits\Query`
- Type: method provider
- Current consumers: `App/Utilities/Query/SchemaQuery.php`
- Public methods: `addColumn`, `removeColumn`, `renameColumn`, `modifyColumn`, `createTable`, `renameTable`, `truncateTable`, `dropTable`, `alterTable`, `addIndex`, `dropIndex`, `renameIndex`, `setConstraint`, `dropConstraint`, `renameConstraint`, `setUnique`, `dropUnique`, `setCheck`, `dropCheck`, `setForeign`, `dropForeign`, `addPrimary`, `dropPrimary`, `setDefault`, `dropDefault`, `createView`, `dropView`, `alterView`, `createTrigger`, `dropTrigger`, `alterTrigger`, `createDatabase`, `dropDatabase`, `alterDatabase`, `createProcedure`, `dropProcedure`, `createFunction`, `dropFunction`, `createSequence`, `dropSequence`
- Private methods: `chainSchema`

### Reflection

#### `ReflectionAttributeTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionAttributeTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionAttribute`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getAttributeArguments`, `getAttributeName`, `getAttributeTarget`, `isAttributeRepeated`, `newAttributeInstance`

#### `ReflectionClassTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionClassTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionClass`, `ReflectionClassConstant`, `ReflectionExtension`, `ReflectionMethod`, `ReflectionObject`, `ReflectionProperty`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `createClass`, `getClassAttributes`, `getClassConstant`, `getClassConstants`, `getClassConstructor`, `getClassDefaultProperties`, `getClassDocComment`, `getClassEndLine`, `getClassExtension`, `getClassExtensionName`, `getClassFileName`, `getClassInterfaceNames`, `getClassInterfaces`, `getClassLazyInitializer`, `getClassMethod`, `getClassMethods`, `getClassModifiers`, `getClassName`, `getClassNamespaceName`, `getClassParent`, `getClassProperties`, `getClassProperty`, `getClassReflectionConstant`, `getClassReflectionConstants`, `getClassShortName`, `getClassStartLine`, `getClassStaticProperties`, `getClassStaticPropertyValue`, `getClassTraitAliases`, `getClassTraitNames`, `getClassTraits`, `hasClassConstant`, `hasClassMethod`, `hasClassProperty`, `implementsClassInterface`, `initializeClassLazyObject`, `isClassInNamespace`, `isClassAbstract`, `isClassAnonymous`, `isClassCloneable`, `isClassEnum`, `isClassFinal`, `isClassInstance`, `isClassInstantiable`, `isClassInterface`, `isClassInternal`, `isClassIterable`, `isClassIterateable`, `isClassReadOnly`, `isClassSubclassOf`, `isClassTrait`, `isClassUninitializedLazyObject`, `isClassUserDefined`, `markClassLazyInitialized`, `newClassInstance`, `newClassInstanceArgs`, `newClassInstanceWithoutConstructor`, `newClassLazyGhost`, `newClassLazyProxy`, `resetClassAsLazyGhost`, `resetClassAsLazyProxy`, `setClassStaticPropertyValue`, `classToString`

#### `ReflectionConstantTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionConstantTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionClass`, `ReflectionClassConstant`, `ReflectionConstant`, `ReflectionExtension`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `createConstant`, `getConstantExtension`, `getConstantExtensionName`, `getConstantFileName`, `getConstantName`, `getConstantNamespaceName`, `getConstantShortName`, `getConstantValue`, `isConstantDeprecated`, `constantToString`, `createClassConstant`, `getClassConstantAttributes`, `getClassConstantDeclaringClass`, `getClassConstantDocComment`, `getClassConstantModifiers`, `getClassConstantName`, `getClassConstantType`, `getClassConstantValue`, `hasClassConstantType`, `isClassConstantDeprecated`, `isClassConstantEnumCase`, `isClassConstantFinal`, `isClassConstantPrivate`, `isClassConstantProtected`, `isClassConstantPublic`, `classConstantToString`

#### `ReflectionEnumTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionEnumTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionEnum`, `ReflectionEnumBackedCase`, `ReflectionEnumUnitCase`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getEnumBackingType`, `getEnumCase`, `getEnumCases`, `enumHasCase`, `isEnumBacked`, `getEnumBackingValue`, `getEnumFromUnitCase`, `getEnumUnitCaseValue`

#### `ReflectionExtensionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionExtensionTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionClass`, `ReflectionExtension`, `ReflectionFunction`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getExtensionClasses`, `getExtensionClassNames`, `getExtensionConstants`, `getExtensionDependencies`, `getExtensionFunctions`, `getExtensionINIEntries`, `getExtensionName`, `getExtensionVersion`, `printExtensionInfo`, `isExtensionPersistent`, `isExtensionTemporary`, `extensionToString`

#### `ReflectionFunctionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionFunctionTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `Closure`, `ReflectionClass`, `ReflectionExtension`, `ReflectionFunction`, `ReflectionFunctionAbstract`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getFunctionClosure`, `invokeFunction`, `invokeFunctionArgs`, `isFunctionAnonymous`, `isFunctionDisabled`, `reflectionFunctionToString`, `getFunctionAttributes`, `getClosureCalledClass`, `getClosureScopeClass`, `getClosureThis`, `getClosureUsedVariables`, `getFunctionDocComment`, `getFunctionEndLine`, `getFunctionExtension`, `getFunctionExtensionName`, `getFunctionFileName`, `getFunctionNamespaceName`, `getFunctionNumberOfParameters`, `getFunctionNumberOfRequiredParameters`, `getFunctionParameters`, `getFunctionName`, `getFunctionReturnType`, `getFunctionShortName`, `getFunctionStartLine`, `getFunctionStaticVariables`, `getFunctionTentativeReturnType`, `functionHasReturnType`, `functionHasTentativeReturnType`, `isFunctionInNamespace`, `isFunctionClosure`, `isFunctionDeprecated`, `isFunctionGenerator`, `isFunctionInternal`, `isFunctionStatic`, `isFunctionUserDefined`, `isFunctionVariadic`, `functionReturnsReference`, `functionToString`

#### `ReflectionGeneratorTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionGeneratorTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `Generator`, `ReflectionFunctionAbstract`, `ReflectionGenerator`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getGeneratorExecutingFile`, `getExecutingGenerator`, `getGeneratorExecutingLine`, `getGeneratorFunction`, `getGeneratorThis`, `getGeneratorTrace`, `isGeneratorClosed`

#### `ReflectionMethodTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionMethodTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `Closure`, `ReflectionClass`, `ReflectionMethod`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getMethodClosure`, `getMethodDeclaringClass`, `getMethodModifiers`, `getMethodPrototype`, `methodHasPrototype`, `invokeMethod`, `invokeMethodArgs`, `isMethodAbstract`, `isConstructorMethod`, `isDestructorMethod`, `isMethodFinal`, `isMethodPrivate`, `isMethodProtected`, `isMethodPublic`, `setMethodAccessible`, `methodToString`

#### `ReflectionParameterTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionParameterTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionClass`, `ReflectionFunctionAbstract`, `ReflectionParameter`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `canParameterBeNull`, `canParameterBePassedByValue`, `getParameterAttributes`, `getParameterDeclaringClass`, `getParameterDeclaringFunction`, `getParameterDefaultValue`, `getParameterDefaultValueConstantName`, `getParameterName`, `getParameterPosition`, `getParameterType`, `hasParameterType`, `isParameterDefaultValueAvailable`, `isParameterDefaultValueConstant`, `isParameterOptional`, `isParameterPassedByReference`, `isParameterPromoted`, `isParameterVariadic`, `parameterToString`

#### `ReflectionPropertyTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionPropertyTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionClass`, `ReflectionProperty`, `ReflectionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getPropertyDeclaringClass`, `getPropertyDefaultValue`, `getPropertyDocComment`, `getPropertyModifiers`, `getPropertyName`, `getPropertyType`, `getPropertyValue`, `hasPropertyDefaultValue`, `hasPropertyType`, `isPropertyDefault`, `isPropertyDynamic`, `isPropertyInitialized`, `isPropertyLazy`, `isPropertyPrivate`, `isPropertyPromoted`, `isPropertyProtected`, `isPropertyPublic`, `isPropertyReadOnly`, `isPropertyStatic`, `setPropertyAccessible`, `setRawPropertyValueWithoutLazyInitialization`, `setPropertyValue`, `skipPropertyLazyInitialization`, `propertyToString`, `getPropertyAttributes`

#### `ReflectionTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `Fiber`, `ReflectionException`, `ReflectionFiber`, `ReflectionReference`, `Reflector`, `Throwable`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `createException`, `getExceptionMessage`, `getExceptionPrevious`, `getExceptionCode`, `getExceptionFile`, `getExceptionLine`, `getExceptionTrace`, `getExceptionTraceAsString`, `exceptionToString`, `createFiber`, `getFiberCallable`, `getFiberExecutingFile`, `getFiberExecutingLine`, `getFiberInstance`, `getFiberTrace`, `createReference`, `getReferenceId`

#### `ReflectionTypeTrait`

- Path: `App/Utilities/Traits/Reflection/ReflectionTypeTrait.php`
- Namespace: `App\Utilities\Traits\Reflection`
- Type: method provider
- Imports: `ReflectionIntersectionType`, `ReflectionNamedType`, `ReflectionType`, `ReflectionUnionType`
- Current consumers: `App/Utilities/Managers/System/ReflectionManager.php`
- Public methods: `getTypeName`, `isBuiltinType`, `canTypeBeNull`, `typeToString`, `getIntersectionTypes`, `getUnionTypes`

### Rules

#### `RuleTrait`

- Path: `App/Utilities/Traits/Rules/RuleTrait.php`
- Namespace: `App\Utilities\Traits\Rules`
- Type: method provider
- Current consumers: `App/Utilities/Sanitation/PatternSanitizer.php`, `App/Utilities/Validation/GeneralValidator.php`, `App/Utilities/Validation/PatternValidator.php`
- Public methods: `ruleRequire`, `ruleMin`, `ruleMax`, `ruleBetween`, `ruleLess`, `ruleGreater`, `ruleMinLength`, `ruleMaxLength`, `ruleLengthBetween`, `ruleInArray`, `ruleNotInArray`, `ruleIsInt`, `ruleIsFloat`, `ruleIsString`, `ruleIsBoolean`, `ruleIsAssociativeArray`, `ruleArrayUnique`, `ruleDivisibleBy`, `ruleNotEmpty`, `ruleStep`, `ruleArraySize`, `ruleSequential`, `ruleStartsWith`, `ruleEndsWith`, `rulePositive`, `ruleNegative`, `ruleArrayNotEmpty`

#### `RulesTrait`

- Path: `App/Utilities/Traits/Rules/RulesTrait.php`
- Namespace: `App\Utilities\Traits\Rules`
- Type: wrapper trait
- Composed traits: `RuleTrait`
- Current consumers: `App/Utilities/Sanitation/GeneralSanitizer.php`

### Sort

#### `DirectorySortTrait`

- Path: `App/Utilities/Traits/Sort/DirectorySortTrait.php`
- Namespace: `App\Utilities\Traits\Sort`
- Type: method provider
- Current consumers: none found outside the traits layer
- Protected methods: `sortByName`, `sortByPath`, `sortByModifiedTime`, `sortByAccessedTime`, `sortByCreationTime`, `sortByPermissions`, `sortByOwner`, `sortByGroup`

#### `FileSortTrait`

- Path: `App/Utilities/Traits/Sort/FileSortTrait.php`
- Namespace: `App\Utilities\Traits\Sort`
- Type: method provider
- Current consumers: none found outside the traits layer
- Protected methods: `sortByName`, `sortByPath`, `sortBySize`, `sortByExtension`, `sortByModifiedTime`, `sortByAccessedTime`, `sortByCreationTime`, `sortByPermissions`, `sortByOwner`, `sortByGroup`
