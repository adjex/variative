<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\UserDefined;

/**
 * Relative Type
 */
class RelativeType extends UserDefinedType {

	private string $name;

	/**
	 * Create a new type.
	 *
	 * @param string $name  The relative name (self, static, parent).
	 * @param string $class The relative class.
	 */
	public function __construct(string $name, string $class) {
		$this->name = $name;

		parent::__construct($class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isRelative(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return $this->name;
	}
}
