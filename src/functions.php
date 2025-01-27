<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use ReflectionType;
use Variative\Exception\ParseException;

if (!function_exists('Variative\type')) {
	/**
	 * Helper for creating a type object.
	 *
	 * @param ReflectionType|string|null                                                                   $input   type source
	 * @param array{self?: class-string, static?: class-string, parent?: class-string, return?: bool}|null $context context data
	 *
	 * @return Type the type
	 *
	 * @throws ParseException if a parse error occurs
	 */
	function type(ReflectionType|string|null $input, Context|array|null $context = null): Type {
		return Type::create($input, $context);
	}
}

if (!function_exists('Variative\context')) {
	/**
	 * Helper for creating a context object.
	 *
	 * @param array{self?: class-string, static?: class-string, parent?: class-string, return?: bool}|null $input context data
	 *
	 * @return Context the context
	 */
	function context(?array $input): Context {
		return Context::create($input);
	}
}
