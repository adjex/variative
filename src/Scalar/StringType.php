<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Scalar;

use Stringable;

class StringType extends ScalarType {
	public function getName(): string {
		return 'string';
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if ($strict) {
			return is_string($value);
		}

		if (is_scalar($value)) {
			// all scalars cast to string
			return true;
		}

		if ($value instanceof Stringable) {
			return true;
		}

		return false;
	}
}
