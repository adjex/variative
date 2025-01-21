<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\ReturnOnly;

use Variative\Special\SpecialType;

abstract class ReturnOnlyType extends SpecialType {

	public function isReturnOnly(): bool {
		return true;
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		return false;
	}
}
