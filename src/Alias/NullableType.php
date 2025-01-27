<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Alias;

use Variative\Composite\UnionType;
use Variative\Special\NullType;
use Variative\Type;

/**
 * Nullable Type.
 */
class NullableType extends UnionType {
	private string $name;

	/**
	 * Create a new nullable type.
	 *
	 * @param Type $type the type to make nullable
	 */
	public function __construct(Type $type) {
		$this->name = '?' . $type->getName();

		parent::__construct(
			new NullType(),
			$type,
		);
	}

	public function getName(): string {
		return $this->name;
	}

	public function isAlias(): bool {
		// this is arguable, it's not an official 'alias' type
		return true;
	}
}
