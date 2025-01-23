<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Common;

use Variative\Exception\ComparisonException;
use Variative\Type;

/**
 * Abstract Base Type
 *
 * @internal
 */
abstract class BaseType extends Type {

	/**
	 * {@inheritDoc}
	 */
	protected function diffWith(Type $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s with %s.',
			$this::class,
			$other::class,
		));
	}

	/**
	 * {@inheritDoc}
	 */
	protected function diffFrom(Type $other): ?int {
		throw new ComparisonException(sprintf(
			'Logic does not exist for calculating the difference of %s from %s.',
			$this::class,
			$other::class,
		));
	}

	/**
	 * {@inheritDoc}
	 */
	public function isBuiltIn(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isScalar(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isCompound(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isSpecial(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReturnOnly(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLiteral(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isClass(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isUserDefined(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isInternal(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isRelative(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAlias(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isComposite(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __toString(): string {
		return $this->getName();
	}
}
