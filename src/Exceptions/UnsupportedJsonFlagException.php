<?php declare(strict_types=1);

namespace SanderMuller\Json\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a flag is passed that this package cannot honour.
 *
 * These are programming errors rather than bad data, so this extends
 * InvalidArgumentException and not JsonException — catching it is almost never
 * the right move; fixing the call is.
 */
final class UnsupportedJsonFlagException extends InvalidArgumentException
{
    public static function objectAsArray(): self
    {
        return new self(
            'JSON_OBJECT_AS_ARRAY cannot be honoured: each method fixes its own object representation. '
            . 'Use Json::array() for an associative array, or Json::object() for a stdClass. '
            . "Porting from guzzle's Utils::jsonDecode(\$json, true)? The replacement is Json::array(\$json) — "
            . 'note that this package\'s second argument is $flags, not $assoc.'
        );
    }

    public static function partialOutputOnError(): self
    {
        return new self(
            'JSON_PARTIAL_OUTPUT_ON_ERROR cannot be honoured: it suppresses JSON_THROW_ON_ERROR, so encoding '
            . 'would silently return a string with unencodable values replaced by null — exactly the silent '
            . 'failure this package exists to prevent. Call json_encode() directly if you want that behaviour.'
        );
    }
}
