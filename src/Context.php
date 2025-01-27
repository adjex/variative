<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

/**
 * @phpstan-type ContextArray array{
 *     self?:   class-string,
 *     static?: class-string,
 *     parent?: class-string,
 *     return?: bool
 * }
 * @phpstan-type ContextType self|ContextArray|null
 */
class Context {
	/** @var class-string|null */
	private ?string $self;

	/** @var class-string|null */
	private ?string $static;

	/** @var class-string|null */
	private ?string $parent;

	private bool $return;

	/**
	 * Create a new context.
	 *
	 * @param class-string|null $self   context self classname (or null if undefined)
	 * @param class-string|null $static context static classname (or null if undefined)
	 * @param class-string|null $parent context parent classname (or null if undefined)
	 * @param bool              $return true if in a return context, false otherwise
	 */
	public function __construct(?string $self = null, ?string $static = null, ?string $parent = null, bool $return = false) {
		$this->self = $self;
		$this->static = $static;
		$this->parent = $parent;
		$this->return = $return;
	}

	/**
	 * Check if context defines self.
	 *
	 * @return bool true if self is defined
	 *
	 * @phpstan-assert-if-true class-string $this->getSelfClass()
	 */
	public function hasSelfClass(): bool {
		return !is_null($this->self);
	}

	/**
	 * Check if context defines static.
	 *
	 * @return bool true if static is defined
	 *
	 * @phpstan-assert-if-true class-string $this->getStaticClass()
	 */
	public function hasStaticClass(): bool {
		return !is_null($this->static);
	}

	/**
	 * Check if context defines parent.
	 *
	 * @return bool true if parent is defined
	 *
	 * @phpstan-assert-if-true class-string $this->getParentClass()
	 */
	public function hasParentClass(): bool {
		return !is_null($this->parent);
	}

	/**
	 * Get self from context.
	 *
	 * @return class-string|null self class or null if undefined
	 */
	public function getSelfClass(): ?string {
		return $this->self;
	}

	/**
	 * Get static from context.
	 *
	 * @return class-string|null static class or null if undefined
	 */
	public function getStaticClass(): ?string {
		return $this->static;
	}

	/**
	 * Get parent from context.
	 *
	 * @return class-string|null parent class or null if undefined
	 */
	public function getParentClass(): ?string {
		return $this->parent;
	}

	/**
	 * Check if context is a return.
	 *
	 * @return bool true if in a return context
	 */
	public function isReturn(): bool {
		return $this->return;
	}

	/**
	 * Create a new context.
	 *
	 * @param ContextArray|null $input context array (or null)
	 *
	 * @return self the context
	 */
	public static function create(?array $input = null): self {
		if (is_array($input)) {
			return self::fromArray($input);
		}

		return self::default();
	}

	/**
	 * Create context from array.
	 *
	 * @param ContextArray $input context array
	 *
	 * @return self the context
	 */
	public static function fromArray(array $input): self {
		$self = null;
		if (isset($input['self']) && self::isValidClassname($input['self'])) {
			$self = $input['self'];
		}

		$static = null;
		if (isset($input['static']) && self::isValidClassname($input['static'])) {
			$static = $input['static'];
		}

		$parent = null;
		if (isset($input['parent']) && self::isValidClassname($input['parent'])) {
			$parent = $input['parent'];
		}

		$return = false;
		if (isset($input['return']) && is_bool($input['return'])) {
			$return = $input['return'];
		}

		return new self(
			self: $self,
			static: $static,
			parent: $parent,
			return: $return,
		);
	}

	/**
	 * Create a default (null) context.
	 *
	 * @return Context the default context
	 */
	public static function default(): self {
		return new self();
	}

	/**
	 * Check if $class is a valid class string.
	 *
	 * @param mixed $class classname to validate
	 *
	 * @return bool true if $class is a valid class string
	 *
	 * @phpstan-assert-if-true class-string $class
	 */
	private static function isValidClassname(mixed $class): bool {
		if (!is_string($class)) {
			return false;
		}

		if (class_exists($class, true)) {
			return true;
		}

		if (interface_exists($class, true)) {
			return true;
		}

		// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.enum_existsFound
		if (function_exists('enum_exists') && enum_exists($class, true)) {
			return true;
		}

		return false;
	}
}
