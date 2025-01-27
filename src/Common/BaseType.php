<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Common;

use Variative\Type;

/**
 * Abstract Base Type.
 *
 * @internal
 */
abstract class BaseType extends Type {
	public function isBuiltIn(): bool {
		return false;
	}

	public function isScalar(): bool {
		return false;
	}

	public function isCompound(): bool {
		return false;
	}

	public function isSpecial(): bool {
		return false;
	}

	public function isReturnOnly(): bool {
		return false;
	}

	public function isLiteral(): bool {
		return false;
	}

	public function isClass(): bool {
		return false;
	}

	public function isUserDefined(): bool {
		return false;
	}

	public function isInternal(): bool {
		return false;
	}

	public function isRelative(): bool {
		return false;
	}

	public function isAlias(): bool {
		return false;
	}

	public function isComposite(): bool {
		return false;
	}
}
