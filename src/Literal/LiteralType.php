<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Literal;

use Variative\Common\BuiltInType;
use Variative\Scalar\ScalarType;
use Variative\Type;

/**
 * Abstract Literal Type
 */
abstract class LiteralType extends BuiltInType {

	/**
	 * {@inheritDoc}
	 */
	protected function diffWith(Type $other): ?int {
		if ($other instanceof LiteralType) {
			if ($this->getValue() === $other->getValue()) {
				return self::BIVARIANT;
			}

			return self::INVARIANT;
		}

		if ($other instanceof ScalarType) {
			if ($other->acceptsValue($this->getValue())) {
				return self::COVARIANT;
			}

			return self::INVARIANT;
		}

		return parent::diffWith($other);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isLiteral(): bool {
		return true;
	}

	/**
	 * Get the literal value.
	 *
	 * @return mixed The value.
	 */
	abstract public function getValue(): mixed;

	/**
	 * {@inheritDoc}
	 */
	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if ($strict) {
			return ($value === $this->getValue());
		} else {
			return ($value == $this->getValue());
		}
	}
}
