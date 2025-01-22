<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use Variative\Alias\DefaultReturnType;
use Variative\Alias\DefaultType;
use Variative\Exception\ComparisonException;
use Variative\Exception\ParseException;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionType;
use Stringable;
use WeakMap;

/**
 * @phpstan-import-type ContextArray from Context
 */
abstract class Type implements Stringable {

	/** @var WeakMap<LoggerInterface, bool> $loggers */
	private static WeakMap $loggers;

	protected const COVARIANT     = -1;
	protected const CONTRAVARIANT = 1;
	protected const BIVARIANT     = 0;
	protected const INVARIANT     = null;

	/**
	 * @param ReflectionType|string|null $input
	 * @param Context|ContextArray|null  $context
	 *
	 * @return self
	 *
	 * @throws ParseException
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
	 * @param ReflectionType            $reflector
	 * @param Context|ContextArray|null $context
	 *
	 * @return self
	 *
	 * @throws ParseException
	 */
	public static function fromReflector(ReflectionType $reflector, Context|array|null $context = null): self {
		$context = Context::normalize($context);

		return Parser::parse((string) $reflector, $context);
	}

	/**
	 * @param string                    $string
	 * @param Context|ContextArray|null $context
	 *
	 * @return self
	 *
	 * @throws ParseException
	 */
	public static function fromString(string $string, Context|array|null $context = null): self {
		$context = Context::normalize($context);

		return Parser::parse($string, $context);
	}

	/**
	 * @param mixed                     $value
	 * @param Context|ContextArray|null $context
	 *
	 * @return self
	 *
	 * @throws ParseException
	 */
	public static function fromValue(mixed $value, Context|array|null $context = null): self {
		$context = Context::normalize($context);

		return Parser::parse(gettype($value), $context);
	}

	/**
	 * @param Context|ContextArray|null $context
	 *
	 * @return self
	 */
	public static function default(Context|array|null $context = null): self {
		$context = Context::normalize($context);

		if ($context->isReturn()) {
			return new DefaultReturnType();
		}

		return new DefaultType();
	}

	/**
	 * @param self $left
	 * @param self $right
	 *
	 * @return ?int
	 */
	public static function compare(Type $left, Type $right): ?int {
		$debug = [];

		self::debug(sprintf(
			'compare "%s" to "%s"',
			$left->getName(),
			$right->getName(),
		));

		self::info(sprintf(
			'"%s"->diffWith("%s")',
			$left->getName(),
			$right->getName(),
		));
		try {
			$result = $left->diffWith($right);
			self::debug('Matched, ' . self::diffToString($result));
			return self::normalDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			self::debug('Unmatched, skipping...');
		}

		self::info(sprintf(
			'"%s"->diffFrom("%s")',
			$left->getName(),
			$right->getName(),
		));
		try {
			$result = $left->diffFrom($right);
			self::debug('Matched, ' . self::diffToString($result));
			return self::invertDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			self::debug('Unmatched, skipping...');
		}

		self::info(sprintf(
			'"%s"->diffWith("%s")',
			$right->getName(),
			$left->getName(),
		));
		try {
			$result = $right->diffWith($left);
			self::debug('Matched, ' . self::diffToString($result));
			return self::invertDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			self::debug('Unmatched, skipping...');
		}

		self::info(sprintf(
			'"%s"->diffFrom("%s")',
			$right->getName(),
			$left->getName(),
		));
		try {
			$result = $right->diffFrom($left);
			self::debug('Matched, ' . self::diffToString($result));
			return self::normalDiff($result);
		} catch (ComparisonException $exception) {
			// ignore
			self::debug('Unmatched, skipping...');
		}

		self::info('No matching rules found, returning invariant...');
		return self::INVARIANT;
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

	final public static function attachLogger(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			self::$loggers = new WeakMap();
		}

		self::$loggers[$logger] = true;
	}

	final public static function detachLogger(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			return;
		}

		unset(self::$loggers[$logger]);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function emergency(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function alert(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function critical(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function error(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function warning(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function notice(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function info(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::INFO, $message, $context);
	}

	/**
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	final protected static function debug(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * @param string            $level
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	private static function log(string $level, string|Stringable $message, array $context = []): void {
		if (!isset(self::$loggers)) {
			return;
		}

		foreach (self::$loggers as $logger => $valid) {
			try {
				$logger->log($level, $message, $context);
			} catch (PsrInvalidArgumentException $exception) {
				// impossible, only called with valid log level constants
			}
		}
	}

	/**
	 * Get type name in disjunctive normal form.
	 *
	 * @return string The type name.
	 */
	abstract public function getName(): string;

	/**
	 * Check if type is a built-in.
	 *
	 * @return bool True if type is built-in.
	 */
	abstract public function isBuiltIn(): bool;

	/**
	 * Check if type is scalar (bool, int, float, string).
	 *
	 * @return bool True if type is scalar.
	 */
	abstract public function isScalar(): bool;

	/**
	 * Check if type is compond (array, object, callable).
	 *
	 * @return bool True if type is compound.
	 */
	abstract public function isCompound(): bool;

	/**
	 * Check if type is special (null, resource).
	 *
	 * @return bool True if type is special.
	 */
	abstract public function isSpecial(): bool;

	/**
	 * Check if type is return-only (void, never).
	 *
	 * @return bool True if type is return-only.
	 */
	abstract public function isReturnOnly(): bool;

	/**
	 * Check if type is literal.
	 *
	 * @return bool True if type is literal.
	 */
	abstract public function isLiteral(): bool;

	/**
	 * Check if type is a class.
	 *
	 * @return bool True if type is a class.
	 */
	abstract public function isClass(): bool;

	/**
	 * Check if type is user-defined.
	 *
	 * @return bool True if type is user-defined.
	 */
	abstract public function isUserDefined(): bool;

	/**
	 * Check if type is relative.
	 *
	 * @return bool True if type is relative.
	 */
	abstract public function isRelative(): bool;

	/**
	 * Check if type is internal.
	 *
	 * @return bool True if type is internal.
	 */
	abstract public function isInternal(): bool;

	/**
	 * Check if type is alias (mixed, iterable, etc).
	 *
	 * @return bool True if type is alias.
	 */
	abstract public function isAlias(): bool;

	/**
	 * Check if type is a composite type (union or intersection).
	 *
	 * @return bool True if type is a composite type.
	 */
	abstract public function isComposite(): bool;

	/**
	 * Check value compatibility.
	 *
	 * @param mixed   $value  The value to check.
	 * @param boolean $strict Enable strict type checking.
	 *
	 * @return boolean True if provided value is compatible with this type.
	 */
	abstract public function acceptsValue(mixed $value, bool $strict = true): bool;


	/**
	 * @param self $other
	 *
	 * @return ?int
	 *
	 * @throws ComparisonException
	 */
	protected function diffWith(self $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s with %s.',
			$this::class,
			$other::class,
		));
	}

	/**
	 * @param self $other
	 *
	 * @return ?int
	 *
	 * @throws ComparisonException
	 */
	protected function diffFrom(self $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s from %s.',
			$this::class,
			$other::class,
		));
	}

	/**
	 * @param self $other
	 *
	 * @return ?int
	 */
	final public function compareTo(self $other): ?int {
		return self::compare($this, $other);
	}

	/**
	 * Check type covariance ($this is subtype of $other).
	 *
	 * @param self $other The type to check.
	 *
	 * @return boolean True if this type is covariant with provided type.
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
	 * @param self $other The type to check.
	 *
	 * @return boolean True if this type is contravariant with provided type.
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
	 * @param self $other The type to check.
	 *
	 * @return boolean True if this type is bivariant with provided type.
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
	 * @param self $other The type to check.
	 *
	 * @return boolean True if this type is invariant with provided type.
	 */
	final public function invariantWith(self $other): bool {
		$diff = $this->compareTo($other);

		if (is_null($diff)) {
			return true;
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function __toString(): string;
}
