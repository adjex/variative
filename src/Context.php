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
 * @phpstan-type ContextArray array{'self'?: class-string, 'static'?: class-string, 'parent'?: class-string, 'return'?: bool}
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
	 * @param ContextType $input
	 *
	 * @return self
	 */
	public static function normalize(self|array|null $input = null): self {
		if ($input instanceof self) {
			return $input;
		}

		if (is_array($input)) {
			return new self(
				$input['self']   ?? null,
				$input['static'] ?? null,
				$input['parent'] ?? null,
				$input['return'] ?? false,
			);
		}

		return new self();
	}
}
