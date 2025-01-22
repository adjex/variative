<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\UserDefined;

use Variative\Common\AtomicType;
use Variative\Compound\ObjectType;
use Variative\Exception\ComparisonException;
use Variative\Type;

class UserDefinedType extends AtomicType {
	private string $class;

	public function __construct(string $class) {
		$this->class = $class;
	}

	protected function diffWith(Type $other): ?int {
		if ($other instanceof ObjectType) {
			return self::COVARIANT;
		}

		if ($other instanceof UserDefinedType) {
			if (!$this->classExists()) {
				self::warning(sprintf(
					'%s: "%s" does not exist',
					self::class,
					$this->getClass(),
				));

				throw new ComparisonException(sprintf(
					'Class/Interface "%s" does not exist.',
					$this->getClass(),
				));
			}

			if (!$other->classExists()) {
				self::warning(sprintf(
					'%s: "%s" does not exist',
					self::class,
					$other->getClass(),
				));
				throw new ComparisonException(sprintf(
					'Class/Interface "%s" does not exist.',
					$other->getClass(),
				));
			}

			$covariant = is_a($this->getClass(), $other->getClass(), true);
			$contravariant = is_a($other->getClass(), $this->getClass(), true);

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

	public function getName(): string {
		return $this->class;
	}

	public function isUserDefined(): bool {
		return true;
	}

	public function acceptsValue(mixed $value, bool $strict = true): bool {
		if (!is_object($value)) {
			return false;
		}

		return is_a($value, $this->getClass(), false);
	}

	public function getClass(): string {
		return $this->class;
	}

	private function classExists(): bool {
		if (class_exists($this->getClass(), true)) {
			return true;
		}

		if (interface_exists($this->getClass(), true)) {
			return true;
		}

		// @phan-suppress-next-line PhanUndeclaredFunction
		if (function_exists('enum_exists') && enum_exists($this->getClass(), true)) {
			return true;
		}

		return false;
	}
}
