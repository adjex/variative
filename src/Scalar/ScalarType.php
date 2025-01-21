<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Scalar;

use Variative\Common\BuiltInType;

abstract class ScalarType extends BuiltInType {

	public function isScalar(): bool {
		return true;
	}
}
