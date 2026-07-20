<?php declare(strict_types=1);

namespace SanderMuller\Json;

use JsonException;
use SanderMuller\Json\Exceptions\UnexpectedJsonShapeException;
use stdClass;

/**
 * Typed JSON encoding and decoding that always throws on failure.
 *
 * Every method forces JSON_THROW_ON_ERROR, so a silent `false`/`null` return is
 * impossible. The shape methods additionally assert what the JSON decoded into,
 * which turns `mixed` into a type a static analyser can act on.
 */
final class Json
{
    private function __construct() {}

    /**
     * Encode a value, throwing on failure.
     *
     * @param int<1, max> $depth
     * @throws JsonException
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string
    {
        return json_encode($value, $flags | JSON_THROW_ON_ERROR, $depth);
    }

    /**
     * Encode a value for human eyes: indented, with slashes and unicode left alone.
     *
     * @param int<1, max> $depth
     * @throws JsonException
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
     * @throws JsonException
     */
    public static function decode(string $json, int $flags = 0, int $depth = 512): mixed
    {
        return json_decode($json, associative: false, depth: $depth, flags: $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Decode to an associative array, asserting the JSON described an object or an array.
     *
     * @param int<1, max> $depth
     * @return array<array-key, mixed>
     *
     * @throws JsonException
     */
    public static function array(string $json, int $flags = 0, int $depth = 512): array
    {
        $decoded = json_decode($json, associative: true, depth: $depth, flags: $flags | JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw UnexpectedJsonShapeException::expected('an array', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to a list, asserting the JSON described an array rather than an object.
     *
     * @param int<1, max> $depth
     * @return list<mixed>
     *
     * @throws JsonException
     */
    public static function list(string $json, int $flags = 0, int $depth = 512): array
    {
        $decoded = self::array($json, $flags, $depth);

        if (! array_is_list($decoded)) {
            throw UnexpectedJsonShapeException::expected('a list', $decoded);
        }

        return $decoded;
    }

    /**
     * Decode to a stdClass, asserting the JSON described an object.
     *
     * @param int<1, max> $depth
     * @throws JsonException
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
     * Decode to a string, asserting the JSON described a string.
     *
     * @param int<1, max> $depth
     * @throws JsonException
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
     * @param int<1, max> $depth
     * @throws JsonException
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
     * @throws JsonException
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
     * @throws JsonException
     */
    public static function bool(string $json, int $flags = 0, int $depth = 512): bool
    {
        $decoded = self::decode($json, $flags, $depth);

        if (! is_bool($decoded)) {
            throw UnexpectedJsonShapeException::expected('a bool', $decoded);
        }

        return $decoded;
    }
}
