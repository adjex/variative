<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Literal;

class FalseType extends LiteralType {

	public function getName(): string {
		return 'false';
	}

	public function getValue(): bool {
		return false;
	}
}
