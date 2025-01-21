<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Literal;

class TrueType extends LiteralType {

	public function getName(): string {
		return 'true';
	}

	public function getValue(): bool {
		return true;
	}
}
