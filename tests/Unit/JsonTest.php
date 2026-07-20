<?php declare(strict_types=1);

use SanderMuller\Json\Exceptions\UnexpectedJsonShapeException;
use SanderMuller\Json\Exceptions\UnsupportedJsonFlagException;
use SanderMuller\Json\Json;

describe('encode', function (): void {
    it('encodes a value', function (): void {
        expect(Json::encode(['a' => 1]))->toBe('{"a":1}');
    });

    it('encodes a JsonSerializable through its jsonSerialize()', function (): void {
        $value = new class implements JsonSerializable {
            /**
             * @return array<string, string>
             */
            public function jsonSerialize(): array
            {
                return ['from' => 'jsonSerialize'];
            }
        };

        expect(Json::encode($value))->toBe('{"from":"jsonSerialize"}');
    });

    it('encodes only the public properties of a plain object', function (): void {
        $value = new class {
            public string $pub = 'x';

            protected string $prot = 'y';

            private string $priv = 'z';

            public function priv(): string
            {
                return $this->priv;
            }
        };

        expect(Json::encode($value))->toBe('{"pub":"x"}');
    });

    it('throws on a value that cannot be encoded', function (): void {
        Json::encode(NAN);
    })->throws(JsonException::class);

    it('throws on invalid utf-8', function (): void {
        Json::encode(['a' => "\xB1\x31"]);
    })->throws(JsonException::class, 'Malformed UTF-8 characters, possibly incorrectly encoded');

    it('throws on a type it cannot represent', function (): void {
        Json::encode(['r' => fopen('php://memory', 'r')]);
    })->throws(JsonException::class, 'Type is not supported');

    it('substitutes invalid utf-8 when asked to', function (): void {
        expect(Json::encode(['a' => "\xB1\x31"], JSON_INVALID_UTF8_SUBSTITUTE))->toBe('{"a":"\\ufffd1"}');
    });

    it('encodes right up to the depth limit', function (): void {
        expect(Json::encode([[[1]]], depth: 3))->toBe('[[[1]]]');
    });

    it('throws when the value nests deeper than the depth allows', function (): void {
        Json::encode([[[1]]], depth: 2);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');

    it('honours caller-supplied flags alongside the forced throw flag', function (): void {
        expect(Json::encode(['url' => 'a/b'], JSON_UNESCAPED_SLASHES))->toBe('{"url":"a/b"}');
    });

    it('still throws when the caller passes their own flags', function (): void {
        Json::encode(NAN, JSON_UNESCAPED_SLASHES);
    })->throws(JsonException::class);

    it('refuses JSON_PARTIAL_OUTPUT_ON_ERROR, which would defeat the throw guarantee', function (): void {
        Json::encode(['a' => 1], JSON_PARTIAL_OUTPUT_ON_ERROR);
    })->throws(UnsupportedJsonFlagException::class, 'JSON_PARTIAL_OUTPUT_ON_ERROR cannot be honoured');
});

describe('pretty', function (): void {
    it('indents and leaves slashes and unicode alone', function (): void {
        expect(Json::pretty(['url' => 'a/b', 'name' => 'café']))
            ->toBe("{\n    \"url\": \"a/b\",\n    \"name\": \"café\"\n}");
    });

    it('forwards caller-supplied flags', function (): void {
        expect(Json::pretty(['a'], JSON_FORCE_OBJECT))->toBe("{\n    \"0\": \"a\"\n}");
    });

    it('forwards the depth', function (): void {
        Json::pretty([[[1]]], depth: 2);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
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
    })->throws(JsonException::class, 'Syntax error');

    it('throws on an empty string', function (): void {
        Json::decode('');
    })->throws(JsonException::class, 'Syntax error');

    it('throws on whitespace-only input', function (): void {
        Json::decode('   ');
    })->throws(JsonException::class, 'Syntax error');

    it('decodes right up to the depth limit', function (): void {
        expect(Json::decode('[[[1]]]', depth: 4))->toBe([[[1]]]);
    });

    it('throws when the json nests deeper than the depth allows', function (): void {
        Json::decode('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');

    it('forwards caller-supplied flags', function (): void {
        expect(Json::decode('12345678901234567890', JSON_BIGINT_AS_STRING))->toBe('12345678901234567890');
    });

    it('loses precision on a big integer unless asked not to', function (): void {
        expect(Json::decode('12345678901234567890'))->toBeFloat();
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

    it('throws on an empty string', function (): void {
        Json::array('');
    })->throws(JsonException::class, 'Syntax error');

    it('forwards caller-supplied flags', function (): void {
        expect(Json::array('{"big":12345678901234567890}', JSON_BIGINT_AS_STRING))
            ->toBe(['big' => '12345678901234567890']);
    });

    it('forwards the depth', function (): void {
        Json::array('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
});

describe('list', function (): void {
    it('decodes a json array to a list', function (): void {
        expect(Json::list('["a","b"]'))->toBe(['a', 'b']);
    });

    it('decodes an empty array', function (): void {
        expect(Json::list('[]'))->toBeArray()
            ->toBeEmpty();
    });

    it('rejects an object', function (): void {
        Json::list('{"a":1}');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a list, got array.');

    it('reports its own shape rather than the array it decodes through', function (): void {
        Json::list('"hi"');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a list, got string.');

    it('accepts an object whose keys are sequential integers, since that is indistinguishable after decoding', function (): void {
        expect(Json::list('{"0":"a","1":"b"}'))->toBe(['a', 'b']);
    });

    it('forwards caller-supplied flags', function (): void {
        expect(Json::list('[12345678901234567890]', JSON_BIGINT_AS_STRING))->toBe(['12345678901234567890']);
    });

    it('forwards the depth', function (): void {
        Json::list('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
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

    it('forwards caller-supplied flags', function (): void {
        expect(Json::object('{"big":12345678901234567890}', JSON_BIGINT_AS_STRING)->big)
            ->toBe('12345678901234567890');
    });

    it('forwards the depth', function (): void {
        Json::object('{"a":{"b":{"c":1}}}', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
});

describe('string', function (): void {
    it('decodes a string', function (): void {
        expect(Json::string('"hi"'))->toBe('hi');
    });

    it('rejects a number', function (): void {
        Json::string('1');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a string, got int.');

    it('names stdClass when an object was decoded', function (): void {
        Json::string('{"a":1}');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to a string, got stdClass.');

    it('keeps the digits of a big integer when asked to', function (): void {
        expect(Json::string('12345678901234567890', JSON_BIGINT_AS_STRING))->toBe('12345678901234567890');
    });

    it('forwards the depth', function (): void {
        Json::string('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
});

describe('int', function (): void {
    it('decodes an integer', function (): void {
        expect(Json::int('42'))->toBe(42);
    });

    it('rejects a float', function (): void {
        Json::int('4.2');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an int, got float.');

    it('rejects an integer too large for PHP, which decodes to a float', function (): void {
        Json::int('12345678901234567890');
    })->throws(UnexpectedJsonShapeException::class, 'Expected the JSON to decode to an int, got float.');

    it('rejects a numeric string', function (): void {
        Json::int('"42"');
    })->throws(UnexpectedJsonShapeException::class);

    it('forwards the depth', function (): void {
        Json::int('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
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

    it('forwards the depth', function (): void {
        Json::float('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
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

    it('forwards the depth', function (): void {
        Json::bool('[[[1]]]', depth: 3);
    })->throws(JsonException::class, 'Maximum stack depth exceeded');
});

describe('JSON_OBJECT_AS_ARRAY', function (): void {
    it('is refused by every decode method, so a mis-ported $assoc argument cannot pass silently', function (string $method): void {
        match ($method) {
            'decode' => Json::decode('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'array' => Json::array('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'list' => Json::list('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'object' => Json::object('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'string' => Json::string('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'int' => Json::int('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'float' => Json::float('{"a":1}', JSON_OBJECT_AS_ARRAY),
            'bool' => Json::bool('{"a":1}', JSON_OBJECT_AS_ARRAY),
            default => throw new LogicException("Unhandled method {$method}."),
        };
    })
        ->with(['decode', 'array', 'list', 'object', 'string', 'int', 'float', 'bool'])
        ->throws(UnsupportedJsonFlagException::class, 'JSON_OBJECT_AS_ARRAY cannot be honoured');

    it('points a porter at the right replacement', function (): void {
        Json::decode('{"a":1}', JSON_OBJECT_AS_ARRAY);
    })->throws(UnsupportedJsonFlagException::class, 'The replacement is Json::array($json)');
});

describe('round trip', function (): void {
    $payload = [
        'object' => ['nested' => ['deep' => true]],
        'list' => [1, 2, 3],
        'null' => null,
        'bool' => false,
        'float' => 4.2,
        'unicode' => 'café',
        'slash' => 'a/b',
        'emptyArray' => [],
    ];

    it('survives encode then decode', function () use ($payload): void {
        expect(Json::array(Json::encode($payload)))->toBe($payload);
    });

    it('survives pretty then decode', function () use ($payload): void {
        expect(Json::array(Json::pretty($payload)))->toBe($payload);
    });
});

it('cannot be instantiated', function (): void {
    expect((new ReflectionClass(Json::class))->getConstructor()?->isPrivate())->toBeTrue();
});

it('reports a shape failure as a native JsonException', function (): void {
    Json::array('"hi"');
})->throws(UnexpectedJsonShapeException::class);

it('does not let an unsupported flag masquerade as a JsonException', function (): void {
    $caughtAsJsonException = false;

    try {
        try {
            Json::decode('{"a":1}', JSON_OBJECT_AS_ARRAY);
        } catch (JsonException) {
            $caughtAsJsonException = true;
        }
    } catch (UnsupportedJsonFlagException) {
        // Expected: it escapes the JsonException catch, because a bad flag is a bad call, not bad data.
    }

    expect($caughtAsJsonException)->toBeFalse();
});
