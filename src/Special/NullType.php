<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Special;

class NullType extends SpecialType {
	public function getName(): string {
		return 'null';
	}

	public function acceptsValue(mixed $value, bool $strict = false): bool {
		return is_null($value);
	}
}
