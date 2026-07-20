# Changelog

All notable changes to `sandermuller/json` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.0.0 - 2026-07-20

Initial release.

### Added

- `Json::encode()` and `Json::pretty()` — encoding that always throws on failure.
- `Json::decode()` — decoding that always throws on failure, returning `mixed`.
- Shape methods that assert what the JSON decoded into and narrow the return type: `Json::array()`, `Json::list()`, `Json::object()`, `Json::string()`, `Json::int()`, `Json::float()`, `Json::bool()`.
- `UnexpectedJsonShapeException`, extending the native `JsonException`, so one catch covers both malformed JSON and an unexpected shape.

Replaces `GuzzleHttp\Utils::jsonEncode()` / `jsonDecode()`, deprecated in guzzle 7.15 and removed in 8.0. See the README for the migration table.

## [Unreleased]

### Added

- `Json::encode()` and `Json::pretty()` — encoding that always throws on failure.
- `Json::decode()` — decoding that always throws on failure, returning `mixed`.
- Shape methods that assert what the JSON decoded into and narrow the return type: `Json::array()`, `Json::list()`, `Json::object()`, `Json::string()`, `Json::int()`, `Json::float()`, `Json::bool()`.
- `UnexpectedJsonShapeException`, extending the native `JsonException`, so one catch covers both malformed JSON and an unexpected shape.
