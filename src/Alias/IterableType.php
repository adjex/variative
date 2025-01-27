<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Alias;

use Traversable;
use Variative\Composite\UnionType;
use Variative\Compound\ArrayType;
use Variative\UserDefined\UserDefinedType;

/**
 * Represents an 'iterable' type (alias of 'array|Traversable').
 */
class IterableType extends UnionType {
	/**
	 * Create a new iterable type.
	 */
	public function __construct() {
		parent::__construct(
			new ArrayType(),
			new UserDefinedType(Traversable::class),
		);
	}

	public function getName(): string {
		return 'iterable';
	}

	public function isAlias(): bool {
		return true;
	}
}
