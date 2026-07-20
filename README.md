# Json

[![run-tests](https://github.com/sandermuller/json/actions/workflows/run-tests.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/run-tests.yml)
[![phpstan](https://github.com/sandermuller/json/actions/workflows/phpstan.yml/badge.svg)](https://github.com/sandermuller/json/actions/workflows/phpstan.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)
[![Total Downloads](https://img.shields.io/packagist/dt/sandermuller/json.svg)](https://packagist.org/packages/sandermuller/json)

Typed JSON encoding and decoding for PHP with strict error handling and shape assertions.

`json_decode()` returns `mixed`, so every caller writes the same `is_array()` guard by hand or lies to the type system with a docblock. This package does the guard once and gives you a return type your static analyser can use.

It also replaces `GuzzleHttp\Utils::jsonDecode()` and `GuzzleHttp\Utils::jsonEncode()`, [deprecated in guzzle 7.15 and removed in 8.0](https://github.com/guzzle/guzzle/blob/master/UPGRADING.md).

## Installation

```bash
composer require sandermuller/json
```

Requires PHP 8.3+. No runtime dependencies.

## Usage

Every method throws on malformed input, so there is no silent `false` or `null` to forget to check. The two methods that can return `null` say so in their names.

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

`Json::list()` is the useful one under PHPStan: `json_decode($json, true)` gives you `array`, which is not a `list`, so a `@return list<T>` signature forces a redundant `array_values()`. `Json::list()` returns `list<mixed>` directly.

### When a wrong shape is expected

Sometimes the JSON parses fine but holds the wrong shape, and that is a case you handle locally. `arrayOrNull()` and `objectOrNull()` relax the shape check and leave everything else alone:

```php
Json::arrayOrNull('"hi"');    // null
Json::objectOrNull('[1,2]');  // null
Json::arrayOrNull('{');       // still throws: malformed input is bad data, not a shape
```

A JSON `null` comes back as `null` too, the same as any other shape that is not the one you asked for.

### Normalising a value

`Json::normalize()` deep-converts a value into plain nested arrays by round-tripping it through JSON. It replaces the `Json::array(Json::encode($value))` idiom:

```php
Json::normalize($stdClass);           // array<array-key, mixed>
Json::normalize($jsonSerializable);   // whatever jsonSerialize() returns, flattened
Json::normalizeNullable(null);        // null
```

JSON's own rules apply: only public properties survive, and `INF`, `NAN`, resources, and malformed UTF-8 all throw. A value that does not encode to an object or array is a shape failure, exactly as it would be through `Json::array()`. `normalizeNullable()` excuses a null input; everything else still fails.

Both methods run at the default depth and accept no flags. Anything `Json::encode()` can represent survives the round trip, since the decode leg gets one extra level (decoding `[[[1]]]` needs `4` where encoding it needs `3`). For control over flags or depth, do the round trip by hand with `Json::encode()` and `Json::array()`.

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

Passing an unsupported flag throws `UnsupportedJsonFlagException`, which extends `InvalidArgumentException` rather than `JsonException`: it means the call is wrong, not the data.

The reported type is the type the JSON *decoded to*, which a flag can shift: `Json::int('9223372036854775808', JSON_BIGINT_AS_STRING)` reports `got string` even though the JSON held an integer.

### Flags and depth

Both are passthroughs, positioned so the common case stays short. `JSON_THROW_ON_ERROR` is always added on top of whatever you pass:

```php
Json::object($raw, JSON_BIGINT_AS_STRING);
Json::encode($value, JSON_UNESCAPED_UNICODE);
Json::decode($raw, depth: 8);
```

Two flags throw `UnsupportedJsonFlagException` instead of being honoured:

| Flag | Why |
|---|---|
| `JSON_PARTIAL_OUTPUT_ON_ERROR` (encode) | Suppresses `JSON_THROW_ON_ERROR`, returning a string with unencodable values replaced by `null`. |
| `JSON_OBJECT_AS_ARRAY` (decode) | Each method fixes its own object representation, so the flag could only ever be discarded. |

`$depth` below `1` is a programming error. Encode throws `JsonException` and decode throws `ValueError`, matching PHP's own behaviour in each case.

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

## Changelog

[CHANGELOG.md](https://github.com/sandermuller/json/blob/main/CHANGELOG.md) has the release history. [PUBLIC_API.md](PUBLIC_API.md) lists the semver-protected surface, so you can tell which changes are breaking.

## License

MIT. See [LICENSE](LICENSE).
