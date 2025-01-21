<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Composite;

use Variative\Type;
use Variative\Common\AtomicType;

class UnionType extends CompositeType {

	protected const SPLICE = '|';
	protected const PREFIX = '';
	protected const SUFFIX = '';

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

	protected function diffWith(Type $other): ?int {
		if ($other instanceof AtomicType) {
			// $this is a subtype of $other if ALL of its composite types are subtypes
			$covariant = true;
			foreach ($this->getTypes() as $type) {
				if (!$type->covariantWith($other)) {
					$covariant = false;
					break;
				}
			}

			// $this is a supertype of $other if ANY of its composite types are supertypes
			$contravariant = false;
			foreach ($this->getTypes() as $type) {
				if ($type->contravariantWith($other)) {
					$contravariant = true;
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

		if ($other instanceof IntersectionType) {
			// unions can only be supertypes of intersection types?

			// $this is a supertype if ANY composite types are supertypes
			foreach ($this->getTypes() as $type) {
				if ($type->contravariantWith($other)) {
					return self::CONTRAVARIANT;
				}
			}

			//return self::NONCOMPARABLE;
		}

		if ($other instanceof self) {
			// $this is a subtype of $other if ALL of $this's composite types are a subtype of $other
			// A|B <= A|B|C : A && B && C
			// A|C <= A|B : A && C extends B
			$covariant = true;
			foreach ($this->getTypes() as $localType) {
				foreach ($other->getTypes() as $otherType) {
					if ($localType->covariantWith($otherType)) {
						continue 2;
					}
				}

				$covariant = false;
				break;
			}

			// $this is a supertype of $other if all of $other's composite types have a cooresponding supertype in $this
			// A|B|C >= A|B : A && B && C
			// A|B >= A|C : A && C extends B
			$contravariant = true;
			foreach ($other->getTypes() as $otherType) {
				foreach ($this->getTypes() as $localType) {
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

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		foreach ($this->getTypes() as $type) {
			if (!$type->acceptsValue($value, $strict)) {
				return false;
			}
		}

		return true;
	}
}
