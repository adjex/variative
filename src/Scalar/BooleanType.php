<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Scalar;

/**
 * Boolean Type
 */
class BooleanType extends ScalarType {

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return 'bool';
	}

	/**
	 * {@inheritDoc}
	 */
	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if ($strict) {
			return is_bool($value);
		}

		return is_scalar($value);
	}
}
