<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Composite;

use Variative\Common\BaseType;
use Variative\Type;

abstract class CompositeType extends BaseType {

	protected const SPLICE = '';
	protected const PREFIX = '';
	protected const SUFFIX = '';

	/** @var array<Type> */
	private array $types = [];

	public function __construct(Type $type, Type ...$types) {
		$this->addType($type);

		foreach ($types as $additional) {
			$this->addType($additional);
		}
	}

	private function addType(Type $type): void {
		$this->types[] = $type;
	}

	public function isReturnOnly(): bool {
		foreach ($this->types as $type) {
			if ($type->isReturnOnly()) {
				return true;
			}
		}

		return false;
	}

	public function isComposite(): bool {
		return true;
	}

	/**
	 * @return array<Type>
	 */
	public function getTypes(): array {
		return $this->types;
	}

	public function getName(): string {
		$types = [];

		foreach ($this->types as $type) {
			if ($type instanceof self && !$type->isAlias() && count($type->getTypes()) > 1) {
				$types[] = '(' . $type->getName() . ')';
			} else {
				$types[] = $type->getName();
			}
		}

		return static::PREFIX . implode(static::SPLICE, $types) . static::SUFFIX;
	}
}
