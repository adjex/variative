<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Alias;

/**
 * Default Type
 */
class DefaultType extends MixedType {

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return '[mixed]';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAlias(): bool {
		// this is arguable, it's not an official 'alias' type
		return true;
	}
}
