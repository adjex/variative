<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Composite;

use Variative\Common\AtomicType;
use Variative\Type;

/**
 * Intersection Type
 */
class IntersectionType extends CompositeType {

	protected const SPLICE = '&';
	protected const PREFIX = '';
	protected const SUFFIX = '';

	/**
	 * {@inheritDoc}
	 */
	public function getTypes(): array {
		$types = [];
		foreach (parent::getTypes() as $type) {
			if ($type instanceof self) {
				foreach ($type->getTypes() as $subtype) {
					$types[] = $subtype;
				}
			} else {
				$types[] = $type;
			}
		}

		return $types;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function diffWith(Type $other): ?int {
		if ($other instanceof AtomicType) {
			// $this is a subtype of $other if ANY of its composite types are subtypes
			$covariant = false;
			foreach ($this->getTypes() as $type) {
				if ($type->covariantWith($other)) {
					$covariant = true;
					break;
				}
			}

			// $this is a supertype of $other if ALL of its composite types are supertypes
			$contravariant = true;
			foreach ($this->getTypes() as $type) {
				if (!$type->contravariantWith($other)) {
					$contravariant =  false;
					break;
				}
			}

			if ($covariant && $contravariant) {
				return self::BIVARIANT;
			}

			if ($covariant) {
				return self::COVARIANT;
			}

			if ($contravariant) {
				return self::CONTRAVARIANT;
			}

			return self::INVARIANT;
		}

		if ($other instanceof UnionType) {
			// intersections can only be subtypes of union types?

			// $this is a subtype if ANY composite types are subtypes
			foreach ($this->getTypes() as $type) {
				if ($type->covariantWith($other)) {
					return self::COVARIANT;
				}
			}

			//return self::NONCOMPARABLE;
		}

		if ($other instanceof self) {
			// $this is a subtype of $other if ALL of $other's composite types have a cooresponding subtype in $this
			// A&B&C <= A&B
			// C&D <= A&B : C extends A && D extends B
			$covariant = true;
			foreach ($other->getTypes() as $otherType) {
				$tested = [];
				foreach ($this->getTypes() as $localType) {
					$tested[] = (string) $localType;
					if ($localType->covariantWith($otherType)) {
						continue 2;
					}
				}

				$covariant = false;
				break;
			}

			// $this is a supertype of $other if ALL of $this's composite types have a cooresponding supertype in $other
			// A&B >= A&B&C
			// A&B >= C&D : C extends A && D extends B
			$contravariant = true;
			foreach ($this->getTypes() as $localType) {
				foreach ($other->getTypes() as $otherType) {
					if ($localType->contravariantWith($otherType)) {
						continue 2;
					}
				}

				$contravariant = false;
				break;
			}

			if ($covariant && $contravariant) {
				return self::BIVARIANT;
			}

			if ($covariant) {
				return self::COVARIANT;
			}

			if ($contravariant) {
				return self::CONTRAVARIANT;
			}

			return self::INVARIANT;
		}

		return parent::diffWith($other);
	}

	/**
	 * {@inheritDoc}
	 */
	public function acceptsValue(mixed $value, bool $strict = true): bool {
		foreach ($this->getTypes() as $type) {
			if (!$type->acceptsValue($value, $strict)) {
				return false;
			}
		}

		return true;
	}
}
