<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\ReturnOnly;

use Variative\Type;

/**
 * Never Type
 */
class NeverType extends ReturnOnlyType {

	/**
	 * {@inheritDoc}
	 */
	protected function diffWith(Type $other): ?int {
		if ($other instanceof self) {
			return self::BIVARIANT;
		}

		return self::COVARIANT;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function diffFrom(Type $other): ?int {
		if ($other instanceof self) {
			return self::BIVARIANT;
		}

		return self::CONTRAVARIANT;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return 'never';
	}
}
