<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Compound;

use Variative\Common\BuiltInType;

/**
 * Abstract Compound Type.
 */
abstract class CompoundType extends BuiltInType {
	public function isCompound(): bool {
		return true;
	}
}
