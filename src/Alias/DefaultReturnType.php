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
use Variative\ReturnOnly\VoidType;

class DefaultReturnType extends UnionType {

	public function __construct() {
		parent::__construct(
			new MixedType(),
			new VoidType(),
		);
	}

	public function getName(): string {
		return '[mixed|void]';
	}

	public function isAlias(): bool {
		// this is arguable, it's not an official 'alias' type
		return true;
	}
}
