<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Alias;

use Variative\Composite\UnionType;
use Variative\Compound\ArrayType;
use Variative\Compound\ObjectType;
use Variative\Special\CallableType;
use Variative\Special\NullType;
use Variative\Special\ResourceType;
use Variative\Scalar\BooleanType;
use Variative\Scalar\FloatType;
use Variative\Scalar\IntegerType;
use Variative\Scalar\StringType;

/**
 * Mixed Type
 */
class MixedType extends UnionType {

	/**
	 * Create a new mixed type.
	 */
	public function __construct() {
		parent::__construct(
			new ObjectType(),
			new ResourceType(),
			new ArrayType(),
			new StringType(),
			new FloatType(),
			new IntegerType(),
			new BooleanType(),
			new NullType(),
			new CallableType(),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return 'mixed';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAlias(): bool {
		return true;
	}
}
