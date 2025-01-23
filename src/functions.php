<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use Variative\Exception\ParseException;
use ReflectionType;

if (!function_exists('Variative\type')) {
	/**
	 * Helper for creating a type object.
	 *
	 * @param ReflectionType|string|null                                                                           $input
	 * @param array{self?: class-string, static?: class-string, parent?: class-string, return?: bool}|null $context
	 *
	 * @return Type
	 *
	 * @throws ParseException
	 */
	function type(ReflectionType|string|null $input, Context|array|null $context = null): Type {
		return Type::create($input, $context);
	}
}

if (!function_exists('Variative\context')) {
	/**
	 * Helper for creating a context object.
	 *
	 * @param array{self?: class-string, static?: class-string, parent?: class-string, return?: bool}|null $input
	 *
	 * @return Context
	 */
	function context(array|null $input): Context {
		return Context::create($input);
	}
}
