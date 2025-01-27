<?php

/*
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
 * Negative Type.
 */
class NegativeType extends CompositeType {
	protected const SPLICE = '';
	protected const PREFIX = '!';
	protected const SUFFIX = '';

	/**
	 * Create a new negative type.
	 *
	 * @param Type $type type to negate
	 */
	public function __construct(Type $type) {
		parent::__construct($type);
	}

	public function isReturnOnly(): bool {
		// unlike other composite types, negative types can NEVER be return-only
		return false;
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		// not sure this works, pretty vague for negative types
		// !bool shouldn't accept a boolean value
		// but !string should accept a Stringable object under non-strict which this would disallow
		// possibly strict on all subtype checking should be enabled, yeah that probably works best

		foreach ($this->getTypes() as $type) {
			if ($type->acceptsValue($value, $strict)) {
				return false;
			}
		}

		return true;
	}

	protected function diffWith(Type $other): ?int {
		if ($other instanceof self) {
			// $this is a subtype of $other if it's composite type is a subtype

			// between two negative types, the relationships are reversed
			// eg if A is a subtype of B, then !A is a supertype of !B
			// this is because !A excludes more types than !B, so it includes FEWER types

			$localType = $this->getTypes()[0];
			$otherType = $other->getTypes()[0];

			return $otherType->compareTo($localType);
		}

		if ($other instanceof AtomicType) {
			// negative types can never be subtypes of non-negative types

			// $this is a supertype of $other if NONE of its composite types are supertypes
			$localType = $this->getTypes()[0];
			if (!$localType->contravariantWith($other)) {
				return self::CONTRAVARIANT;
			}

			return self::INVARIANT;
		}

		if ($other instanceof CompositeType) {
			// when comparing to other composite types we need to explode this type
			// negatives can only be supertypes of non-negative composite types?

			// negatives can be more general than unions !A >= B|C
			// negatives can be more general than intersections !A >= B&C

			// $this is a supertype if ALL composite types are NOT supertypes

			$localType = $this->getTypes()[0];
			if (!$localType->contravariantWith($other)) {
				return self::CONTRAVARIANT;
			}
		}

		return parent::diffWith($other);
	}
}
