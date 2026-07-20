<?php declare(strict_types=1);

namespace SanderMuller\Json\Exceptions;

use JsonException;

/**
 * Thrown when JSON decodes successfully but not into the shape that was asked for.
 *
 * Extends the native JsonException so a single `catch (JsonException)` covers both
 * malformed input and an unexpected shape.
 */
final class UnexpectedJsonShapeException extends JsonException
{
    /**
     * @internal the wording of $expected is not a compatibility promise; catch the
     *           exception rather than constructing it
     */
    public static function expected(string $expected, mixed $actual): self
    {
        return new self("Expected the JSON to decode to {$expected}, got " . get_debug_type($actual) . '.');
    }
}
