<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\UserDefined;

class RelativeType extends UserDefinedType {

	private string $name;

	public function __construct(string $name, string $class) {
		$this->name = $name;

		parent::__construct($class);
	}

	public function isRelative(): bool {
		return true;
	}

	public function getName(): string {
		return $this->name;
	}
}
