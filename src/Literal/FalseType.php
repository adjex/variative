<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Literal;

/**
 * False Type
 */
class FalseType extends LiteralType {

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return 'false';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValue(): bool {
		return false;
	}
}
