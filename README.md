# Json

[![run-tests](https://github.com/sandermuller/json/actions/workflows/run-tests.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/run-tests.yml)
[![phpstan](https://github.com/sandermuller/json/actions/workflows/phpstan.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/phpstan.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)
[![Total Downloads](https://img.shields.io/packagist/dt/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)

Typed JSON encoding and decoding for PHP with strict error handling and shape assertions.

`json_decode()` returns `mixed`. That is honest, but it means every caller either writes the same `is_array()` guard by hand, or lies to the type system with a docblock. This package does the guard once, so the return type is one your static analyser can actually use.

It also replaces `GuzzleHttp\Utils::jsonDecode()` and `GuzzleHttp\Utils::jsonEncode()`, [deprecated in guzzle 7.15 and removed in 8.0](https://github.com/guzzle/guzzle/blob/master/UPGRADING.md).

## Installation

```bash
composer require sandermuller/json
```

Requires PHP 8.3+. No runtime dependencies.

## Usage

Every method throws on malformed input. There is no silent `false` or `null` to forget to check. The two flags that would undermine that (`JSON_PARTIAL_OUTPUT_ON_ERROR` on encode, `JSON_OBJECT_AS_ARRAY` on decode) are rejected rather than silently ignored.

```php
use SanderMuller\Json\Json;

Json::encode(['name' => 'hihaho']);         // '{"name":"hihaho"}'
Json::pretty(['name' => 'hihaho']);         // indented, slashes and unicode left alone
```

### Shapes

Each shape method decodes and asserts, so the return type is narrow:

```php
Json::array('{"a":1}');       // array<array-key, mixed>  (object or array)
Json::list('["a","b"]');      // list<mixed>              (array only)
Json::object('{"a":1}');      // stdClass                 (object only)
Json::string('"hi"');         // string
Json::int('42');              // int
Json::float('4.2');           // float  (ints are widened)
Json::bool('true');           // bool
Json::decode($raw);           // mixed  (when the shape is genuinely unknown)
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

Passing an unsupported flag throws `UnsupportedJsonFlagException`, which extends `InvalidArgumentException`. It is deliberately *not* a `JsonException`, because it means the call is wrong, not the data.

The reported type is the type the JSON *decoded to*, which a flag can shift: `Json::int('9223372036854775808', JSON_BIGINT_AS_STRING)` reports `got string` even though the JSON held an integer.

### Flags and depth

Both are passthroughs, positioned so the common case stays short. `JSON_THROW_ON_ERROR` is always added on top of whatever you pass:

```php
Json::object($raw, JSON_BIGINT_AS_STRING);
Json::encode($value, JSON_UNESCAPED_UNICODE);
Json::decode($raw, depth: 8);
```

Two flags are rejected instead of honoured, because both would reintroduce a silent failure:

| Flag | Why |
|---|---|
| `JSON_PARTIAL_OUTPUT_ON_ERROR` (encode) | Suppresses `JSON_THROW_ON_ERROR`, returning a string with unencodable values replaced by `null`. |
| `JSON_OBJECT_AS_ARRAY` (decode) | Each method fixes its own object representation, so the flag could only ever be discarded. |

`$depth` below `1` is a programming error: encode throws `JsonException`, decode throws `ValueError`. That is PHP's own behaviour in each case, and the package does not paper over the difference.

## Replacing `GuzzleHttp\Utils`

| Deprecated | Replacement |
|---|---|
| `Utils::jsonEncode($v)` | `Json::encode($v)` |
| `Utils::jsonEncode($v, $flags)` | `Json::encode($v, $flags)` |
| `Utils::jsonDecode($j)` | `Json::decode($j)`, or `Json::object($j)` when it is always an object |
| `Utils::jsonDecode($j, true)` | `Json::array($j)` |

Two differences to port carefully:

The second argument changed meaning. Guzzle's was `bool $assoc`; here it is `int $flags`. `Json::decode($json, true)` is therefore *not* the port of `Utils::jsonDecode($json, true)`; use `Json::array($json)`. From a caller without `declare(strict_types=1)`, that `true` would otherwise coerce to `1` (`JSON_OBJECT_AS_ARRAY`) and quietly hand back a `stdClass`. That exact flag is rejected with an error message naming the correct replacement, so the mistake fails loudly instead.

The exception type changed. Guzzle threw `GuzzleHttp\Exception\InvalidArgumentException` (an SPL `InvalidArgumentException`); this package throws `JsonException`. Update `catch` blocks accordingly.

## Testing

```bash
composer test
composer qa
```

## License

MIT. See [LICENSE](LICENSE).
