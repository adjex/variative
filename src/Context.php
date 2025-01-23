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

	/** @var ?class-string $self */
	private ?string $self;

	/** @var ?class-string $static */
	private ?string $static;

	/** @var ?class-string $parent */
	private ?string $parent;

	/** @var bool $return */
	private bool $return;

	/**
	 * @param ?class-string $self
	 * @param ?class-string $static
	 * @param ?class-string $parent
	 * @param bool          $return
	 */
	public function __construct(?string $self = null, ?string $static = null, ?string $parent = null, $return = false) {
		$this->self = $self;
		$this->static = $static;
		$this->parent = $parent;
		$this->return = $return;
	}

	/**
	 * @phpstan-assert-if-true class-string $this->getSelfClass()
	 */
	public function hasSelfClass(): bool {
		return !is_null($this->self);
	}

	/**
	 * @phpstan-assert-if-true class-string $this->getStaticClass()
	 */
	public function hasStaticClass(): bool {
		return !is_null($this->static);
	}

	/**
	 * @phpstan-assert-if-true class-string $this->getParentClass()
	 */
	public function hasParentClass(): bool {
		return !is_null($this->parent);
	}

	/**
	 * @return ?class-string
	 */
	public function getSelfClass(): ?string {
		return $this->self;
	}

	/**
	 * @return ?class-string
	 */
	public function getStaticClass(): ?string {
		return $this->static;
	}

	/**
	 * @return ?class-string
	 */
	public function getParentClass(): ?string {
		return $this->parent;
	}

	/**
	 * @return bool
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
	 * @param ContextArray|null $input
	 */
	public static function create(array|null $input = null): self {
		if (is_array($input)) {
			return self::fromArray($input);
		}

		return self::default();
	}

	/**
	 * @param ContextArray $input
	 *
	 * @return self
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

	public static function default(): self {
		return new self();
	}
}
