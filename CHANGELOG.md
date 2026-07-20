# Changelog

All notable changes to `sandermuller/json` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v0.2.0 - 2026-07-20

<!-- verified-sha: 8481a0daf3f2a342d16b3c162e79d88eaf5ef550 -->
Four new methods, all additive. Nothing existing changed, so upgrading from `0.1.0` is a version bump.

Each one comes from a real call site found while migrating a production consumer off `GuzzleHttp\Utils`, not from guesswork about what might be useful.

#### Added

**`Json::arrayOrNull()` and `Json::objectOrNull()`** relax the shape assertion and nothing else. A valid JSON document of the wrong shape returns `null`; malformed input still throws. That split matters for callers where a wrong shape is a case they handle rather than an error, and who were otherwise stuck hand-writing an `is_array()` guard around native `json_decode`.

```php
Json::arrayOrNull('"hi"');    // null
Json::objectOrNull('[1,2]');  // null
Json::arrayOrNull('{');       // still throws

```
A JSON `null` is reported as `null` too, like any other shape that was not the one asked for.

There is no `listOrNull`, and no scalar variants. Nothing asked for them, and the surface is small on purpose.

**`Json::normalize()`** deep-converts a value into plain nested arrays by round-tripping it through JSON, replacing the `Json::array(Json::encode($value))` idiom. **`Json::normalizeNullable()`** does the same but passes a `null` input straight through.

```php
Json::normalize($stdClass);           // array<array-key, mixed>
Json::normalizeNullable(null);        // null

```
JSON's own rules apply: only public properties survive, and `INF`, `NAN`, resources, and malformed UTF-8 throw rather than degrade quietly. A value that does not encode to an object or array is a shape failure, exactly as it would be through `Json::array()`.

#### Two design decisions worth knowing

**Neither normalize method takes `$flags`.** One integer cannot mean the same thing on both legs of a round trip: `JSON_HEX_TAG` and `JSON_OBJECT_AS_ARRAY` are both `1`, and `JSON_HEX_AMP` and `JSON_BIGINT_AS_STRING` are both `2`. A forwarded encode flag would arrive at the decode as an unrelated decode flag. Do the round trip by hand if that control is needed.

**`normalize()` decodes at depth 513 rather than 512.** The two legs count depth differently, so matching the numbers would have made `normalize()` reject the outermost nesting level that `Json::encode()` accepts. Anything `encode()` can represent now survives the round trip.

**On the naming**: `normalizeNullable()` is deliberately not `normalizeOrNull()`. In `arrayOrNull()` and `objectOrNull()`, `OrNull` means a wrong shape yields `null`. `normalizeNullable()` relaxes what may be passed *in*, not what the JSON is allowed to contain, and a wrong shape there still throws. Two names for two contracts.

#### Internal

Test suite grew from 77 to 112 tests (167 assertions). The `JSON_OBJECT_AS_ARRAY` rejection matrix now covers the new decode methods, so a mis-ported `$assoc` argument still fails loudly on every path.

### What's Changed

* build(deps): Bump actions/cache from 5 to 6 by @dependabot[bot] in https://github.com/SanderMuller/json/pull/1

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/SanderMuller/json/pull/1

**Full Changelog**: https://github.com/SanderMuller/json/compare/v0.1.0...v0.2.0

## v0.1.0 - 2026-07-20

First release.

Typed JSON encoding and decoding that always throws on failure, replacing `GuzzleHttp\Utils::jsonEncode()` / `jsonDecode()` — deprecated in guzzle 7.15, removed in 8.0. See the README for the migration table.

### Added

- `Json::encode()` and `Json::pretty()` — encoding that always throws on failure.
- `Json::decode()` — decoding that always throws on failure, returning `mixed`.
- Shape methods that assert what the JSON decoded into and narrow the return type: `Json::array()`, `Json::list()`, `Json::object()`, `Json::string()`, `Json::int()`, `Json::float()`, `Json::bool()`.
- `UnexpectedJsonShapeException`, extending the native `JsonException`, so one catch covers both malformed JSON and an unexpected shape.
- `UnsupportedJsonFlagException`, extending `InvalidArgumentException` rather than `JsonException`, because an unsupported flag means the call is wrong, not the data.

### Rejected flags

Two flags are refused instead of honoured, because either would reintroduce a silent failure:

- `JSON_PARTIAL_OUTPUT_ON_ERROR` on encode — it suppresses `JSON_THROW_ON_ERROR`, returning a well-formed string with unencodable values silently replaced by `null`.
- `JSON_OBJECT_AS_ARRAY` on decode — each method fixes its own object representation, so the flag could only ever be discarded. Refusing it also makes the mis-port `Json::decode($json, true)` fail loudly: from a caller without `declare(strict_types=1)`, that `true` coerces to exactly this flag. The correct port of `Utils::jsonDecode($json, true)` is `Json::array($json)`, and the error message says so.

### Note on versioning

`0.x`, so a minor bump may break API. Published as `0.1.0` rather than `1.0.0` to leave room to act on real-world usage before freezing the surface.

## [Unreleased]
