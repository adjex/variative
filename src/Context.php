<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

/**
 * Context
 *
 * @phpstan-type ContextArray array{
 *     self?:   class-string,
 *     static?: class-string,
 *     parent?: class-string,
 *     return?: bool
 * }
 *
 * @phpstan-type ContextType self|ContextArray|null
 */
class Context {

	/** @var class-string|null $self */
	private ?string $self;

	/** @var class-string|null $static */
	private ?string $static;

	/** @var class-string|null $parent */
	private ?string $parent;

	/** @var bool $return */
	private bool $return;

	/**
	 * Create a new context.
	 *
	 * @param class-string|null $self   Context self classname (or null if undefined).
	 * @param class-string|null $static Context static classname (or null if undefined).
	 * @param class-string|null $parent Context parent classname (or null if undefined).
	 * @param boolean           $return True if in a return context, false otherwise.
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
	 * @return boolean True if self is defined.
	 *
	 * @phpstan-assert-if-true class-string $this->getSelfClass()
	 */
	public function hasSelfClass(): bool {
		return !is_null($this->self);
	}

	/**
	 * Check if context defines static.
	 *
	 * @return boolean True if static is defined.
	 *
	 * @phpstan-assert-if-true class-string $this->getStaticClass()
	 */
	public function hasStaticClass(): bool {
		return !is_null($this->static);
	}

	/**
	 * Check if context defines parent.
	 *
	 * @return boolean True if parent is defined.
	 *
	 * @phpstan-assert-if-true class-string $this->getParentClass()
	 */
	public function hasParentClass(): bool {
		return !is_null($this->parent);
	}

	/**
	 * Get self from context.
	 *
	 * @return class-string|null Self class or null if undefined.
	 */
	public function getSelfClass(): ?string {
		return $this->self;
	}

	/**
	 * Get static from context.
	 *
	 * @return class-string|null Static class or null if undefined.
	 */
	public function getStaticClass(): ?string {
		return $this->static;
	}

	/**
	 * Get parent from context.
	 *
	 * @return class-string|null Parent class or null if undefined.
	 */
	public function getParentClass(): ?string {
		return $this->parent;
	}

	/**
	 * Check if context is a return.
	 *
	 * @return boolean True if in a return context.
	 */
	public function isReturn(): bool {
		return $this->return;
	}

	/**
	 * Check if $class is a valid class string.
	 *
	 * @param mixed $class Classname to validate.
	 *
	 * @return bool True if $class is a valid class string.
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

		// @phan-suppress-next-line PhanUndeclaredFunction
		if (function_exists('enum_exists') && enum_exists($class, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Create a new context.
	 *
	 * @param ContextArray|null $input Context array (or null).
	 *
	 * @return self The context.
	 */
	public static function create(array|null $input = null): self {
		if (is_array($input)) {
			return self::fromArray($input);
		}

		return self::default();
	}

	/**
	 * Create context from array.
	 *
	 * @param ContextArray $input Context array.
	 *
	 * @return self The context.
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
			self:   $self,
			static: $static,
			parent: $parent,
			return: $return,
		);
	}

	/**
	 * Create a default (null) context.
	 *
	 * @return Context The default context.
	 */
	public static function default(): self {
		return new self();
	}
}
