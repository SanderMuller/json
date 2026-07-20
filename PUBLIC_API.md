# Public API

The semver-protected surface of `sandermuller/json`. Anything documented here is covered by semver; anything outside is internal and may change without notice (including in patch releases).

## Versioning

This package follows [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html). Pre-`1.0.0` releases may break API in MINOR bumps; we surface those in `CHANGELOG.md`.

## Stable surface

### Classes

- `SanderMuller\Json\Json` — static entry point for all encoding and decoding.
- `SanderMuller\Json\Exceptions\UnexpectedJsonShapeException` — thrown when JSON is valid but decodes to a different shape than the called method asserts. Extends the native `JsonException`. The exception message text is not part of the stable surface.
- `SanderMuller\Json\Exceptions\UnsupportedJsonFlagException` — thrown when a passed flag cannot be honoured. Extends `InvalidArgumentException`, not `JsonException`, because it signals a wrong call rather than bad data.

### Methods

All `Json` methods are static and force `JSON_THROW_ON_ERROR`. `$flags` is OR-ed onto that, except that `JSON_PARTIAL_OUTPUT_ON_ERROR` (encode) and `JSON_OBJECT_AS_ARRAY` (decode) are rejected with `UnsupportedJsonFlagException`. `$depth` is a passthrough.

- `Json::encode(mixed $value, int $flags = 0, int $depth = 512): string`
- `Json::pretty(mixed $value, int $flags = 0, int $depth = 512): string` — adds `JSON_PRETTY_PRINT`, `JSON_UNESCAPED_SLASHES`, `JSON_UNESCAPED_UNICODE`
- `Json::decode(string $json, int $flags = 0, int $depth = 512): mixed` — objects become `stdClass`
- `Json::array(string $json, int $flags = 0, int $depth = 512): array<array-key, mixed>`
- `Json::list(string $json, int $flags = 0, int $depth = 512): list<mixed>`
- `Json::object(string $json, int $flags = 0, int $depth = 512): stdClass`
- `Json::string(string $json, int $flags = 0, int $depth = 512): string`
- `Json::int(string $json, int $flags = 0, int $depth = 512): int`
- `Json::float(string $json, int $flags = 0, int $depth = 512): float` — accepts and widens ints
- `Json::bool(string $json, int $flags = 0, int $depth = 512): bool`

### Constants

None.

## Internal (not covered by semver)

Anything marked `@internal` in PHPDoc — currently the named constructors on both exception classes — and the wording of any exception message.

## Removed APIs

<!-- Track removed APIs here so consumers know what was removed when. Example:
- `0.5.0` — Removed `OldClass::oldMethod()`. Migrate to `NewClass::newMethod()`.
-->
