# Json

[![run-tests](https://github.com/sandermuller/json/actions/workflows/run-tests.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/run-tests.yml)
[![phpstan](https://github.com/sandermuller/json/actions/workflows/phpstan.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/phpstan.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)
[![Total Downloads](https://img.shields.io/packagist/dt/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)

Typed JSON encoding and decoding for PHP with strict error handling and shape assertions.

`json_decode()` returns `mixed`. That is honest — but it means every caller either writes the same `is_array()` guard by hand, or lies to the type system with a docblock. This package does the guard once, so the return type is one your static analyser can actually use.

It also replaces `GuzzleHttp\Utils::jsonDecode()` and `GuzzleHttp\Utils::jsonEncode()`, [deprecated in guzzle 7.15 and removed in 8.0](https://github.com/guzzle/guzzle/blob/master/UPGRADING.md).

## Installation

```bash
composer require sandermuller/json
```

Requires PHP 8.3+. No runtime dependencies.

## Usage

Every method throws on malformed input — there is no silent `false` or `null` to forget to check.

```php
use SanderMuller\Json\Json;

Json::encode(['name' => 'hihaho']);         // '{"name":"hihaho"}'
Json::pretty(['name' => 'hihaho']);         // indented, slashes and unicode left alone
```

### Shapes

Each shape method decodes and asserts, so the return type is narrow:

```php
Json::array('{"a":1}');       // array<array-key, mixed>  — object or array
Json::list('["a","b"]');      // list<mixed>              — array only
Json::object('{"a":1}');      // stdClass                 — object only
Json::string('"hi"');         // string
Json::int('42');              // int
Json::float('4.2');           // float  (ints are widened)
Json::bool('true');           // bool
Json::decode('…');            // mixed  — when the shape is genuinely unknown
```

`Json::list()` is the one that pays for itself under PHPStan: `json_decode($json, true)` gives you `array`, which is not a `list`, so a `@return list<T>` signature forces a redundant `array_values()`. `Json::list()` returns `list<mixed>` directly.

### Errors

A shape mismatch throws `UnexpectedJsonShapeException`, which extends the native `JsonException`. One catch covers both malformed JSON and an unexpected shape:

```php
use SanderMuller\Json\Json;

try {
    $config = Json::array($raw);
} catch (JsonException $e) {
    // '{'    → 'Syntax error'
    // '"hi"' → 'Expected the JSON to decode to an array, got string.'
}
```

### Flags and depth

Both are passthroughs, positioned so the common case stays short. `JSON_THROW_ON_ERROR` is always added on top of whatever you pass:

```php
Json::object($raw, JSON_BIGINT_AS_STRING);
Json::encode($value, JSON_UNESCAPED_UNICODE);
Json::decode($raw, depth: 8);
```

## Replacing `GuzzleHttp\Utils`

| Deprecated | Replacement |
|---|---|
| `Utils::jsonEncode($v)` | `Json::encode($v)` |
| `Utils::jsonEncode($v, $flags)` | `Json::encode($v, $flags)` |
| `Utils::jsonDecode($j)` | `Json::decode($j)` — or `Json::object($j)` when it is always an object |
| `Utils::jsonDecode($j, true)` | `Json::array($j)` |

Guzzle threw `GuzzleHttp\Exception\InvalidArgumentException` (which extends the SPL `InvalidArgumentException`); this package throws `JsonException`. Update `catch` blocks accordingly — that is the only behavioural difference.

## Testing

```bash
composer test
composer qa
```

## License

MIT — see [LICENSE](LICENSE).
