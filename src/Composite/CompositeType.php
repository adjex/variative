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

/**
 * Abstract Composite Type
 */
abstract class CompositeType extends BaseType {

	protected const SPLICE = '';
	protected const PREFIX = '';
	protected const SUFFIX = '';

	/** @var array<Type> */
	private array $types = [];

	/**
	 * Create a new composite type.
	 *
	 * @param Type $type     Main type.
	 * @param Type ...$types Additional types.
	 */
	public function __construct(Type $type, Type ...$types) {
		$this->addType($type);

		foreach ($types as $additional) {
			$this->addType($additional);
		}
	}


	private function addType(Type $type): void {
		$this->types[] = $type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReturnOnly(): bool {
		foreach ($this->types as $type) {
			if ($type->isReturnOnly()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isComposite(): bool {
		return true;
	}

	/**
	 * Return all composite subtypes.
	 *
	 * @return array<Type> List of subtypes.
	 */
	public function getTypes(): array {
		return $this->types;
	}

	/**
	 * {@inheritDoc}
	 */
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
