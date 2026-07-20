# Changelog

All notable changes to `sandermuller/json` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
