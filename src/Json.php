<?php declare(strict_types=1);

namespace SanderMuller\Json;

use JsonException;
use SanderMuller\Json\Exceptions\UnexpectedJsonShapeException;
use SanderMuller\Json\Exceptions\UnsupportedJsonFlagException;
use stdClass;

/**
 * Typed JSON encoding and decoding that always throws on failure.
 *
 * Every method forces JSON_THROW_ON_ERROR, and the two flags that would defeat that
 * guarantee are rejected outright, so a silently wrong return value is not reachable.
 * The shape methods additionally assert what the JSON decoded into, which turns `mixed`
 * into a type a static analyser can act on.
 *
 * `$depth` below 1 is out of contract: encode throws a JsonException, decode a ValueError.
 */
final class Json
{
    private function __construct() {}

    /**
     * Encode a value, throwing on failure.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on unencodable input, or if $depth is below 1
     * @throws UnsupportedJsonFlagException if JSON_PARTIAL_OUTPUT_ON_ERROR is passed
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string
    {
        if (($flags & JSON_PARTIAL_OUTPUT_ON_ERROR) !== 0) {
            throw UnsupportedJsonFlagException::partialOutputOnError();
        }

        return json_encode($value, $flags | JSON_THROW_ON_ERROR, $depth);
    }

    /**
     * Encode a value for human eyes: indented, with slashes and unicode left alone.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on unencodable input, or if $depth is below 1
     * @throws UnsupportedJsonFlagException if JSON_PARTIAL_OUTPUT_ON_ERROR is passed
     */
    public static function pretty(mixed $value, int $flags = 0, int $depth = 512): string
    {
        return self::encode($value, $flags | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, $depth);
    }

    /**
     * Decode to whatever the JSON describes — objects become stdClass, arrays become arrays.
     *
     * Reach for this only when the shape is genuinely unknown. The shape methods below
     * narrow the return type instead of handing back mixed.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function decode(string $json, int $flags = 0, int $depth = 512): mixed
    {
        return json_decode($json, associative: false, depth: $depth, flags: self::decodeFlags($flags));
    }

    /**
     * Decode to an associative array, asserting the JSON described an object or an array.
     *
     * @param int<1, max> $depth
     *
     * @return array<array-key, mixed>
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function array(string $json, int $flags = 0, int $depth = 512): array
    {
        $decoded = json_decode($json, associative: true, depth: $depth, flags: self::decodeFlags($flags));

        if (! is_array($decoded)) {
            throw UnexpectedJsonShapeException::expected('an array', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to an associative array, or null if the JSON described something else.
     *
     * Malformed input still throws. Only the shape check is relaxed, for callers that
     * treat a wrong-shaped-but-valid document as an expected condition they handle
     * locally rather than an error. A JSON `null` is reported the same way as any other
     * wrong shape: as null.
     *
     * @param int<1, max> $depth
     *
     * @return array<array-key, mixed>|null
     *
     * @throws JsonException on malformed input
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function arrayOrNull(string $json, int $flags = 0, int $depth = 512): ?array
    {
        $decoded = json_decode($json, associative: true, depth: $depth, flags: self::decodeFlags($flags));

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Decode to a list, asserting the JSON described an array rather than an object.
     *
     * An object whose keys are the sequential integers 0..n-1 is accepted: after decoding
     * it is indistinguishable from a JSON array.
     *
     * @param int<1, max> $depth
     *
     * @return list<mixed>
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function list(string $json, int $flags = 0, int $depth = 512): array
    {
        $decoded = json_decode($json, associative: true, depth: $depth, flags: self::decodeFlags($flags));

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw UnexpectedJsonShapeException::expected('a list', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to a stdClass, asserting the JSON described an object.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function object(string $json, int $flags = 0, int $depth = 512): stdClass
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! $decoded instanceof stdClass) {
            throw UnexpectedJsonShapeException::expected('an object', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to a stdClass, or null if the JSON described something else.
     *
     * Malformed input still throws. Only the shape check is relaxed, for callers that
     * treat a wrong-shaped-but-valid document as an expected condition they handle
     * locally rather than an error. A JSON `null` is reported the same way as any other
     * wrong shape: as null.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function objectOrNull(string $json, int $flags = 0, int $depth = 512): ?stdClass
    {
        $decoded = self::decode($json, $flags, $depth);

        return $decoded instanceof stdClass ? $decoded : null;
    }

    /**
     * Decode to a string, asserting the JSON described a string.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function string(string $json, int $flags = 0, int $depth = 512): string
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! is_string($decoded)) {
            throw UnexpectedJsonShapeException::expected('a string', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to an int, asserting the JSON described an integer.
     *
     * An integer too large for PHP's int decodes to a float, and is therefore rejected.
     * Pass JSON_BIGINT_AS_STRING and use Json::string() to keep the digits.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function int(string $json, int $flags = 0, int $depth = 512): int
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! is_int($decoded)) {
            throw UnexpectedJsonShapeException::expected('an int', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to a float, asserting the JSON described a number.
     *
     * Integers are accepted and widened, mirroring PHP's own int-to-float coercion.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function float(string $json, int $flags = 0, int $depth = 512): float
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! is_int($decoded) && ! is_float($decoded)) {
            throw UnexpectedJsonShapeException::expected('a float', $decoded);
        }

        return (float) $decoded;
    }

    /**
     * Decode to a bool, asserting the JSON described a boolean.
     *
     * @param int<1, max> $depth
     *
     * @throws JsonException on malformed input or an unexpected shape
     * @throws UnsupportedJsonFlagException if JSON_OBJECT_AS_ARRAY is passed
     */
    public static function bool(string $json, int $flags = 0, int $depth = 512): bool
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! is_bool($decoded)) {
            throw UnexpectedJsonShapeException::expected('a bool', $decoded);
        }

        return $decoded;
    }

    /**
     * Deep-convert a value into a plain associative array by round-tripping it through JSON.
     *
     * This is the idiom `Json::array(Json::encode($value))` written once. It flattens
     * stdClass, nested objects, and anything implementing JsonSerializable into plain
     * nested arrays, applying JSON's own conversion rules: only public properties survive,
     * and INF, NAN, resources, and malformed UTF-8 throw rather than degrade.
     *
     * A value that does not encode to a JSON object or array — a bare scalar or null —
     * is a shape failure, exactly as it would be through Json::array(). Use
     * normalizeNullable() when a null input is expected.
     *
     * Neither $flags nor $depth is accepted, because one value cannot mean the same thing
     * on both legs of the round trip. JSON_HEX_TAG and JSON_OBJECT_AS_ARRAY are both 1,
     * and JSON_HEX_AMP and JSON_BIGINT_AS_STRING are both 2, so an encode flag would
     * arrive at the decode as an unrelated decode flag.
     *
     * The decode leg is given 513 rather than the encoding default of 512 because the two
     * legs count depth differently: decoding [[[1]]] needs 4 where encoding it needs 3.
     * Matching the numbers would make normalize() reject the outermost nesting level that
     * encode() accepts, so anything encode() can represent survives the round trip.
     *
     * @return array<array-key, mixed>
     *
     * @throws JsonException if the value cannot be encoded
     * @throws UnexpectedJsonShapeException if the value does not encode to an object or array
     */
    public static function normalize(mixed $value): array
    {
        return self::array(self::encode($value), depth: 513);
    }

    /**
     * Deep-convert a value into a plain associative array, passing null straight through.
     *
     * Named Nullable rather than OrNull because the null here is the caller's, not the
     * document's: it relaxes what may be passed in, where arrayOrNull() and objectOrNull()
     * relax what the JSON is allowed to contain. Only a null input yields null, so a value
     * that encodes to JSON `null` some other way, such as a JsonSerializable returning
     * null, is still a shape failure.
     *
     * @return array<array-key, mixed>|null
     *
     * @throws JsonException if the value cannot be encoded
     * @throws UnexpectedJsonShapeException if the value does not encode to an object or array
     */
    public static function normalizeNullable(mixed $value): ?array
    {
        return $value === null ? null : self::normalize($value);
    }

    /**
     * Every decode method fixes its own object representation, so JSON_OBJECT_AS_ARRAY would
     * be silently discarded. Rejecting it also catches `Json::decode($json, true)` — the
     * mechanical, and wrong, port of `Utils::jsonDecode($json, true)`, since a `true` passed
     * to an int parameter from a non-strict_types caller arrives here as JSON_OBJECT_AS_ARRAY.
     *
     * @throws UnsupportedJsonFlagException
     */
    private static function decodeFlags(int $flags): int
    {
        if (($flags & JSON_OBJECT_AS_ARRAY) !== 0) {
            throw UnsupportedJsonFlagException::objectAsArray();
        }

        return $flags | JSON_THROW_ON_ERROR;
    }
}
