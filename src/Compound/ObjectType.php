<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Compound;

/**
 * Object Type.
 */
class ObjectType extends CompoundType {
	public function getName(): string {
		return 'object';
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		return is_object($value);
	}
}
