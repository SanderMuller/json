<?php declare(strict_types=1);

use SanderMuller\Json\Exceptions\UnexpectedJsonShapeException;
use SanderMuller\Json\Json;

describe('encode', function (): void {
    it('encodes a value', function (): void {
        expect(Json::encode(['a' => 1]))->toBe('{"a":1}');
    });

    it('throws on a value that cannot be encoded', function (): void {
        Json::encode(NAN);
    })->throws(JsonException::class);

    it('throws when the value nests deeper than the depth allows', function (): void {
        Json::encode([[['too deep']]], depth: 2);
    })->throws(JsonException::class);

    it('honours caller-supplied flags alongside the forced throw flag', function (): void {
        expect(Json::encode(['url' => 'a/b'], JSON_UNESCAPED_SLASHES))->toBe('{"url":"a/b"}');
    });

    it('still throws when the caller passes their own flags', function (): void {
        Json::encode(NAN, JSON_UNESCAPED_SLASHES);
    })->throws(JsonException::class);
});

describe('pretty', function (): void {
    it('indents and leaves slashes and unicode alone', function (): void {
        expect(Json::pretty(['url' => 'a/b', 'name' => 'café']))
            ->toBe("{\n    \"url\": \"a/b\",\n    \"name\": \"café\"\n}");
    });
});

describe('decode', function (): void {
    it('decodes an object to stdClass', function (): void {
        expect(Json::decode('{"a":1}'))->toBeInstanceOf(stdClass::class);
    });

    it('decodes a scalar', function (): void {
        expect(Json::decode('"hi"'))->toBe('hi');
    });

    it('decodes null', function (): void {
        expect(Json::decode('null'))->toBeNull();
    });

    it('throws on malformed json', function (): void {
        Json::decode('{');
    })->throws(JsonException::class);

    it('throws when the json nests deeper than the depth allows', function (): void {
        Json::decode('[[["too deep"]]]', depth: 2);
    })->throws(JsonException::class);

    it('honours caller-supplied flags', function (): void {
        expect(Json::object('{"big":12345678901234567890}', JSON_BIGINT_AS_STRING)->big)
            ->toBe('12345678901234567890');
    });
});

describe('array', function (): void {
    it('decodes an object to an associative array', function (): void {
        expect(Json::array('{"a":{"b":1}}'))->toBe(['a' => ['b' => 1]]);
    });

    it('decodes a json array', function (): void {
        expect(Json::array('[1,2]'))->toBe([1, 2]);
    });

    it('rejects a scalar', function (): void {
        Json::array('"hi"');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an array, got string.');

    it('rejects null', function (): void {
        Json::array('null');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an array, got null.');

    it('throws on malformed json', function (): void {
        Json::array('{');
    })->throws(JsonException::class);
});

describe('list', function (): void {
    it('decodes a json array to a list', function (): void {
        expect(Json::list('["a","b"]'))->toBe(['a', 'b']);
    });

    it('decodes an empty array', function (): void {
        expect(Json::list('[]'))
            ->toBeEmpty();
    });

    it('rejects an object', function (): void {
        Json::list('{"a":1}');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a list, got array.');

    it('accepts an object whose keys are sequential integers, since that is indistinguishable after decoding', function (): void {
        expect(Json::list('{"0":"a","1":"b"}'))->toBe(['a', 'b']);
    });

    it('rejects a scalar', function (): void {
        Json::list('1');
    })->throws(UnexpectedJsonShapeException::class);
});

describe('object', function (): void {
    it('decodes an object', function (): void {
        expect(Json::object('{"a":1}')->a)->toBe(1);
    });

    it('rejects a json array', function (): void {
        Json::object('[1,2]');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an object, got array.');

    it('rejects null', function (): void {
        Json::object('null');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an object, got null.');
});

describe('string', function (): void {
    it('decodes a string', function (): void {
        expect(Json::string('"hi"'))->toBe('hi');
    });

    it('rejects a number', function (): void {
        Json::string('1');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a string, got int.');
});

describe('int', function (): void {
    it('decodes an integer', function (): void {
        expect(Json::int('42'))->toBe(42);
    });

    it('rejects a float', function (): void {
        Json::int('4.2');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an int, got float.');

    it('rejects a numeric string', function (): void {
        Json::int('"42"');
    })->throws(UnexpectedJsonShapeException::class);
});

describe('float', function (): void {
    it('decodes a float', function (): void {
        expect(Json::float('4.2'))->toBe(4.2);
    });

    it('widens an integer', function (): void {
        expect(Json::float('42'))->toBe(42.0);
    });

    it('rejects a numeric string', function (): void {
        Json::float('"4.2"');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a float, got string.');
});

describe('bool', function (): void {
    it('decodes true', function (): void {
        expect(Json::bool('true'))->toBeTrue();
    });

    it('decodes false', function (): void {
        expect(Json::bool('false'))->toBeFalse();
    });

    it('rejects an integer', function (): void {
        Json::bool('1');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a bool, got int.');
});

it('reports every shape failure as a native JsonException', function (): void {
    expect(new UnexpectedJsonShapeException('x'))->toBeInstanceOf(JsonException::class);
});
