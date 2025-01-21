<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Scalar;

class IntegerType extends ScalarType {

	public function getName(): string {
		return 'int';
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if ($strict) {
			return is_int($value);
		}

		return is_numeric($value);
	}
}
