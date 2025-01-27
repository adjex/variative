<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use ReflectionType;
use Stringable;
use Variative\Alias\DefaultReturnType;
use Variative\Alias\DefaultType;
use Variative\Exception\ComparisonException;
use Variative\Exception\ParseException;

/**
 * Abstract Type.
 *
 * @phpstan-import-type ContextArray from Context
 */
abstract class Type implements Stringable {
	protected const COVARIANT = -1;
	protected const CONTRAVARIANT = 1;
	protected const BIVARIANT = 0;
	protected const INVARIANT = null;

	final public function __toString(): string {
		return $this->getName();
	}

	/**
	 * Create a new type.
	 *
	 * @param ReflectionType|string|null $input   reflection object or string
	 * @param Context|ContextArray|null  $context context data
	 *
	 * @return self the resulting type
	 *
	 * @throws ParseException if unable to parse the type
	 */
	final public static function create(ReflectionType|string|null $input, Context|array|null $context = null): self {
		if ($input instanceof ReflectionType) {
			return self::fromReflector($input, $context);
		}

		if (is_string($input)) {
			return self::fromString($input, $context);
		}

		return self::default($context);
	}

	/**
	 * Create a new type from reflection.
	 *
	 * @param ReflectionType            $reflector reflection object
	 * @param Context|ContextArray|null $context   context data
	 *
	 * @return self the resulting type
	 *
	 * @throws ParseException if unable to parse the type
	 */
	final public static function fromReflector(ReflectionType $reflector, Context|array|null $context = null): self {
		if (!$context instanceof Context) {
			$context = Context::create($context);
		}

		return Parser::parse((string) $reflector, $context);
	}

	/**
	 * Create a new type from string.
	 *
	 * @param string                    $string  type string
	 * @param Context|ContextArray|null $context context data
	 *
	 * @return self the resulting type
	 *
	 * @throws ParseException if unable to parse the type
	 */
	final public static function fromString(string $string, Context|array|null $context = null): self {
		if (!$context instanceof Context) {
			$context = Context::create($context);
		}

		return Parser::parse($string, $context);
	}

	/**
	 * Create default type from context.
	 *
	 * @param Context|ContextArray|null $context context to use
	 *
	 * @return self the default type
	 */
	final public static function default(Context|array|null $context = null): self {
		if (!$context instanceof Context) {
			$context = Context::create($context);
		}

		if ($context->isReturn()) {
			return new DefaultReturnType();
		}

		return new DefaultType();
	}

	/**
	 * Compare two types.
	 *
	 * Compares $left with $right.
	 *
	 * @param self $left  left type
	 * @param self $right right type
	 *
	 * @return ?int Less than zero if $left is covariant to $right.
	 *              Greater than zero if $left is contravariant to $right.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 */
	final public static function compare(Type $left, Type $right): ?int {
		$debug = [];

		Log::debug(sprintf(
			'compare "%s" to "%s"',
			$left->getName(),
			$right->getName(),
		));

		Log::info(sprintf(
			'"%s"->diffWith("%s")',
			$left->getName(),
			$right->getName(),
		));

		try {
			$result = $left->diffWith($right);
			Log::debug('Matched, ' . self::diffToString($result));

			return self::normalDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			Log::debug('Unmatched, skipping...');
		}

		Log::info(sprintf(
			'"%s"->diffFrom("%s")',
			$left->getName(),
			$right->getName(),
		));

		try {
			$result = $left->diffFrom($right);
			Log::debug('Matched, ' . self::diffToString($result));

			return self::invertDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			Log::debug('Unmatched, skipping...');
		}

		Log::info(sprintf(
			'"%s"->diffWith("%s")',
			$right->getName(),
			$left->getName(),
		));

		try {
			$result = $right->diffWith($left);
			Log::debug('Matched, ' . self::diffToString($result));

			return self::invertDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			Log::debug('Unmatched, skipping...');
		}

		Log::info(sprintf(
			'"%s"->diffFrom("%s")',
			$right->getName(),
			$left->getName(),
		));

		try {
			$result = $right->diffFrom($left);
			Log::debug('Matched, ' . self::diffToString($result));

			return self::normalDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			Log::debug('Unmatched, skipping...');
		}

		Log::info('No matching rules found, returning invariant...');

		return self::INVARIANT;
	}

	/**
	 * Get type name in disjunctive normal form.
	 *
	 * @return string the type name
	 */
	abstract public function getName(): string;

	/**
	 * Check if type is a built-in.
	 *
	 * @return bool true if type is built-in
	 */
	abstract public function isBuiltIn(): bool;

	/**
	 * Check if type is scalar (bool, int, float, string).
	 *
	 * @return bool true if type is scalar
	 */
	abstract public function isScalar(): bool;

	/**
	 * Check if type is compond (array, object, callable).
	 *
	 * @return bool true if type is compound
	 */
	abstract public function isCompound(): bool;

	/**
	 * Check if type is special (null, resource).
	 *
	 * @return bool true if type is special
	 */
	abstract public function isSpecial(): bool;

	/**
	 * Check if type is return-only (void, never).
	 *
	 * @return bool true if type is return-only
	 */
	abstract public function isReturnOnly(): bool;

	/**
	 * Check if type is literal.
	 *
	 * @return bool true if type is literal
	 */
	abstract public function isLiteral(): bool;

	/**
	 * Check if type is a class.
	 *
	 * @return bool true if type is a class
	 */
	abstract public function isClass(): bool;

	/**
	 * Check if type is user-defined.
	 *
	 * @return bool true if type is user-defined
	 */
	abstract public function isUserDefined(): bool;

	/**
	 * Check if type is relative.
	 *
	 * @return bool true if type is relative
	 */
	abstract public function isRelative(): bool;

	/**
	 * Check if type is internal.
	 *
	 * @return bool true if type is internal
	 */
	abstract public function isInternal(): bool;

	/**
	 * Check if type is alias (mixed, iterable, etc).
	 *
	 * @return bool true if type is alias
	 */
	abstract public function isAlias(): bool;

	/**
	 * Check if type is a composite type (union or intersection).
	 *
	 * @return bool true if type is a composite type
	 */
	abstract public function isComposite(): bool;

	/**
	 * Check value compatibility.
	 *
	 * @param mixed $value  the value to check
	 * @param bool  $strict enable strict type checking
	 *
	 * @return bool true if provided value is compatible with this type
	 */
	abstract public function acceptsValue(mixed $value, bool $strict = true): bool;

	/**
	 * Compare type to another type.
	 *
	 * @param self $other the type to compare to
	 *
	 * @return ?int Less than zero if $this is covariant to $other.
	 *              Greater than zero if $this is contravariant to $other.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 */
	final public function compareTo(self $other): ?int {
		return self::compare($this, $other);
	}

	/**
	 * Check type covariance ($this is subtype of $other).
	 *
	 * @param self $other the type to check
	 *
	 * @return bool true if this type is covariant with provided type
	 */
	final public function covariantWith(self $other): bool {
		$diff = $this->compareTo($other);

		if (!is_null($diff) && $diff <= 0) {
			return true;
		}

		return false;
	}

	/**
	 * Check type contravariance ($other is subtype of $this).
	 *
	 * @param self $other the type to check
	 *
	 * @return bool true if this type is contravariant with provided type
	 */
	final public function contravariantWith(self $other): bool {
		$diff = $this->compareTo($other);

		if (!is_null($diff) && $diff >= 0) {
			return true;
		}

		return false;
	}

	/**
	 * Check type bivariance ($this is equivalent to $other).
	 *
	 * @param self $other the type to check
	 *
	 * @return bool true if this type is bivariant with provided type
	 */
	final public function bivariantWith(self $other): bool {
		$diff = $this->compareTo($other);

		if (!is_null($diff) && $diff == 0) {
			return true;
		}

		return false;
	}

	/**
	 * Check type invariance.
	 *
	 * @param self $other the type to check
	 *
	 * @return bool true if this type is invariant with provided type
	 */
	final public function invariantWith(self $other): bool {
		$diff = $this->compareTo($other);

		if (is_null($diff)) {
			return true;
		}

		return false;
	}

	/**
	 * Calculate differene WITH another type.
	 *
	 * @param self $other the type to compare with
	 *
	 * @return ?int Less than zero if $this is covariant to $other.
	 *              Greater than zero if $this is contravariant to $other.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 *
	 * @throws ComparisonException if unable to compare types
	 */
	protected function diffWith(Type $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s with %s.',
			$this::class,
			$other::class,
		));
	}

	/**
	 * Calculate difference FROM another type.
	 *
	 * @param self $other the type to compare from
	 *
	 * @return ?int Less than zero if $other is covariant to $this.
	 *              Greater than zero if $other is contravariant to $other.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 *
	 * @throws ComparisonException if unable to compare types
	 */
	protected function diffFrom(Type $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s from %s.',
			$this::class,
			$other::class,
		));
	}

	private static function normalDiff(?int $result): ?int {
		if (is_null($result)) {
			return self::INVARIANT;
		}

		if ($result < 0) {
			return self::COVARIANT;
		}

		if ($result > 0) {
			return self::CONTRAVARIANT;
		}

		return self::BIVARIANT;
	}

	private static function invertDiff(?int $result): ?int {
		if (is_null($result)) {
			return self::INVARIANT;
		}

		if ($result < 0) {
			return self::CONTRAVARIANT;
		}

		if ($result > 0) {
			return self::COVARIANT;
		}

		return self::BIVARIANT;
	}

	private static function diffToString(?int $diff): string {
		if (is_null($diff)) {
			return 'invariant';
		}

		if ($diff < 0) {
			return 'covariant';
		}

		if ($diff > 0) {
			return 'contravariant';
		}

		return 'bivariant';
	}
}
