<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Common;

use Variative\Type;

abstract class BuiltInType extends AtomicType {

	protected function diffWith(Type $other): ?int {
		if ($other::class == static::class) {
			return self::BIVARIANT;
		}

		return parent::diffWith($other);
	}

	public function isBuiltIn(): bool {
		return true;
	}
}
