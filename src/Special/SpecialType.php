<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Special;

use Variative\Common\BuiltInType;

abstract class SpecialType extends BuiltInType {

	public function isSpecial(): bool {
		return true;
	}
}
