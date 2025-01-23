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
 * Float Type
 */
class FloatType extends ScalarType {

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return 'float';
	}

	/**
	 * {@inheritDoc}
	 */
	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if ($strict) {
			// under strict_types floats DO accept ints
			return (is_float($value) || is_int($value));
		}

		return is_numeric($value);
	}
}
