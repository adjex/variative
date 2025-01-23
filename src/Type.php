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
 * Abstract Type
 *
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
	 * Create a new type.
	 *
	 * @param ReflectionType|string|null $input   Reflection object or string.
	 * @param Context|ContextArray|null  $context Context data.
	 *
	 * @return self The resulting type.
	 *
	 * @throws ParseException If unable to parse the type.
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
	 * @param ReflectionType            $reflector Reflection object.
	 * @param Context|ContextArray|null $context   Context data.
	 *
	 * @return self The resulting type.
	 *
	 * @throws ParseException If unable to parse the type.
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
	 * @param string                    $string  Type string.
	 * @param Context|ContextArray|null $context Context data.
	 *
	 * @return self The resulting type.
	 *
	 * @throws ParseException If unable to parse the type.
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
	 * @param Context|ContextArray|null $context Context to use.
	 *
	 * @return self The default type.
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
	 * @param self $left  Left type.
	 * @param self $right Right type.
	 *
	 * @return ?int Less than zero if $left is covariant to $right.
	 *              Greater than zero if $left is contravariant to $right.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 */
	final public static function compare(Type $left, Type $right): ?int {
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

	/**
	 * Attach a logger.
	 *
	 * @param LoggerInterface $logger The logger to attach.
	 *
	 * @return void
	 */
	final public static function attachLogger(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			self::$loggers = new WeakMap();
		}

		self::$loggers[$logger] = true;
	}

	/**
	 * Detach a logger.
	 *
	 * @param LoggerInterface $logger The logger to detach.
	 *
	 * @return void
	 */
	final public static function detachLogger(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			return;
		}

		unset(self::$loggers[$logger]);
	}

	/**
	 * Log an emergency event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function emergency(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Log an alert event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function alert(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Log a critical event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function critical(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Log an error event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function error(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * Log a warning event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function warning(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Log a notice event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function notice(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Log an info event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	final protected static function info(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::INFO, $message, $context);
	}

	/**
	 * Log a debug event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
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
	 * @return boolean True if type is built-in.
	 */
	abstract public function isBuiltIn(): bool;

	/**
	 * Check if type is scalar (bool, int, float, string).
	 *
	 * @return boolean True if type is scalar.
	 */
	abstract public function isScalar(): bool;

	/**
	 * Check if type is compond (array, object, callable).
	 *
	 * @return boolean True if type is compound.
	 */
	abstract public function isCompound(): bool;

	/**
	 * Check if type is special (null, resource).
	 *
	 * @return boolean True if type is special.
	 */
	abstract public function isSpecial(): bool;

	/**
	 * Check if type is return-only (void, never).
	 *
	 * @return boolean True if type is return-only.
	 */
	abstract public function isReturnOnly(): bool;

	/**
	 * Check if type is literal.
	 *
	 * @return boolean True if type is literal.
	 */
	abstract public function isLiteral(): bool;

	/**
	 * Check if type is a class.
	 *
	 * @return boolean True if type is a class.
	 */
	abstract public function isClass(): bool;

	/**
	 * Check if type is user-defined.
	 *
	 * @return boolean True if type is user-defined.
	 */
	abstract public function isUserDefined(): bool;

	/**
	 * Check if type is relative.
	 *
	 * @return boolean True if type is relative.
	 */
	abstract public function isRelative(): bool;

	/**
	 * Check if type is internal.
	 *
	 * @return boolean True if type is internal.
	 */
	abstract public function isInternal(): bool;

	/**
	 * Check if type is alias (mixed, iterable, etc).
	 *
	 * @return boolean True if type is alias.
	 */
	abstract public function isAlias(): bool;

	/**
	 * Check if type is a composite type (union or intersection).
	 *
	 * @return boolean True if type is a composite type.
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
	 * Calculate differene WITH another type.
	 *
	 * @param self $other The type to compare with.
	 *
	 * @return ?int Less than zero if $this is covariant to $other.
	 *              Greater than zero if $this is contravariant to $other.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 *
	 * @throws ComparisonException If unable to compare types.
	 */
	abstract protected function diffWith(self $other): ?int;

	/**
	 * Calculate difference FROM another type.
	 *
	 * @param self $other The type to compare from.
	 *
	 * @return ?int Less than zero if $other is covariant to $this.
	 *              Greater than zero if $other is contravariant to $other.
	 *              Zero if types are bivariant to each other.
	 *              Null if types are invariant to each other.
	 *
	 * @throws ComparisonException If unable to compare types.
	 */
	abstract protected function diffFrom(self $other): ?int;

	/**
	 * Compare type to another type.
	 *
	 * @param self $other The type to compare to.
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
