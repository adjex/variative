<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use Generator;
use Variative\Alias\IterableType;
use Variative\Alias\MixedType;
use Variative\Alias\NullableType;
use Variative\Composite\IntersectionType;
use Variative\Composite\NegativeType;
use Variative\Composite\UnionType;
use Variative\Compound\ArrayType;
use Variative\Compound\ObjectType;
use Variative\Exception\ParseException;
use Variative\Literal\FalseType;
use Variative\Literal\TrueType;
use Variative\ReturnOnly\NeverType;
use Variative\ReturnOnly\VoidType;
use Variative\Scalar\BooleanType;
use Variative\Scalar\FloatType;
use Variative\Scalar\IntegerType;
use Variative\Scalar\StringType;
use Variative\Special\CallableType;
use Variative\Special\NullType;
use Variative\Special\ResourceType;
use Variative\UserDefined\RelativeType;
use Variative\UserDefined\UserDefinedType;

/**
 * @phpstan-import-type ContextArray from Context
 */
class Parser {
	/**
	 * @var array<string, string>
	 */
	private static array $tokens = [
		'OPEN' => '\(',
		'CLOSE' => '\)',
		'NULLABLE' => '\?',
		'NEGATIVE' => '\!',
		'UNION' => '\|',
		'INTERSECTION' => '\&',
		'IDENTIFIER' => '[a-zA-Z0-9_\\\-]+',
		'WHITESPACE' => '\s',
		'INVALID' => '.',
	];

	private function __construct() {
		// disabled constructor
	}

	/**
	 * Parse a string type.
	 *
	 * @param string                    $input   the string to parse
	 * @param Context|ContextArray|null $context the context to parse within
	 *
	 * @return Type the parsed type
	 *
	 * @throws ParseException if unable to parse the provided input
	 */
	public static function parse(string $input, Context|array|null $context = null): Type {
		if (!$context instanceof Context) {
			$context = Context::create($context);
		}

		return self::generate(self::tokenize($input), $context);
	}

	/**
	 * @return Generator<string, string>
	 */
	private static function tokenize(string $input): Generator {
		foreach (self::$tokens as $name => $pattern) {
			$regex = '/^(' . $pattern . ')(.*)$/';

			if (preg_match($regex, $input, $matches) == 1) {
				yield $name => $matches[1];
				yield from self::tokenize($matches[2]);

				break;
			}
		}
	}

	/**
	 * @throws ParseException
	 */
	private static function collapse(Type ...$types): Type {
		if (count($types) <= 0) {
			throw new ParseException('No Parsable Types Found');
		}

		if (count($types) > 1) {
			return self::createUnion(...$types);
		}

		return array_pop($types);
	}

	/**
	 * @param Generator<string, string> $stream
	 *
	 * @throws ParseException
	 */
	private static function generate(Generator $stream, Context $context, int &$depth = 0, int &$pos = 0): Type {
		$types = [];
		$negate = false;
		$operator = null;

		while ($stream->valid()) {
			$token = $stream->key();
			$value = $stream->current();
			$stream->next();

			$currentPos = $pos;
			$pos += strlen($value);

			if ($token == 'INVALID') {
				throw new ParseException(sprintf(
					'Invalid token "%s" at position %d.',
					$value,
					$currentPos,
				));
			}

			if ($token == 'WHITESPACE') {
				continue;
			}

			if ($token == 'NULLABLE') {
				return self::createNullable(self::generate($stream, $context, $depth, $pos));
			}

			if ($token == 'CLOSE') {
				if ($depth <= 0) {
					throw new ParseException(sprintf(
						'Unexpected "%s" at index %d.',
						$value,
						$currentPos,
					));
				}

				$depth--;

				return self::collapse(...$types);
			}

			if ($token == 'NEGATIVE') {
				$negate = true;

				continue;
			}

			if ($token == 'INTERSECTION' || $token == 'UNION') {
				if (!is_null($operator)) {
					throw new ParseException(sprintf(
						'Unexpected "%s" operator at index %d.',
						$value,
						$currentPos,
					));
				}
				$operator = $token;

				continue;
			}

			// either OPEN or IDENTIFIER
			if ($token == 'OPEN') {
				$depth++;
				$type = self::generate($stream, $context, $depth, $pos);
			} else {
				$type = self::createAtomic($value, $context);
			}

			if ($negate) {
				$type = self::createNegative($type);
			}

			if ($operator == 'INTERSECTION') {
				$prev = array_pop($types);
				if (is_null($prev)) {
					// this is an error
				} elseif ($prev instanceof IntersectionType) {
					$intersectionTypes = $prev->getTypes();
					$intersectionTypes[] = $type;
					$type = self::createIntersection(...$intersectionTypes);
				} else {
					$type = self::createIntersection($prev, $type);
				}
			}

			array_push($types, $type);

			$negate = false;
			$operator = null;
		}

		if ($depth > 0) {
			throw new ParseException(sprintf(
				'Unmatched "%s" token.',
				'OPEN',
			));
		}

		return self::collapse(...$types);
	}

	private static function createAtomic(string $name, Context $context): Type {
		switch (strtolower($name)) {
			case 'bool':
				return new BooleanType();
			case 'int':
				return new IntegerType();
			case 'float':
				return new FloatType();
			case 'string':
				return new StringType();
			case 'array':
				return new ArrayType();
			case 'object':
				return new ObjectType();
			case 'null':
				return new NullType();
			case 'resource':
				return new ResourceType();
			case 'never':
				return new NeverType();
			case 'void':
				return new VoidType();
			case 'true':
				return new TrueType();
			case 'false':
				return new FalseType();
			case 'mixed':
				return new MixedType();
			case 'iterable':
				return new IterableType();
			case 'callable':
				return new CallableType();
			case 'self':
				if ($context->hasSelfClass()) {
					return new RelativeType('self', $context->getSelfClass());
				}

				return new UserDefinedType('self');

			case 'static':
				if ($context->hasStaticClass()) {
					return new RelativeType('static', $context->getStaticClass());
				}

				return new UserDefinedType('static');

			case 'parent':
				if ($context->hasParentClass()) {
					return new RelativeType('parent', $context->getParentClass());
				}

				return new UserDefinedType('parent');

			default:
				return new UserDefinedType($name);
		}
	}

	private static function createNullable(Type $type): Type {
		return new NullableType($type);
	}

	private static function createNegative(Type $type): Type {
		return new NegativeType($type);
	}

	private static function createIntersection(Type $type, Type ...$additional): Type {
		return new IntersectionType($type, ...$additional);
	}

	private static function createUnion(Type $type, Type ...$additional): Type {
		return new UnionType($type, ...$additional);
	}
}
