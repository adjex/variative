<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Test\Comparison;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use Variative\Log;
use Variative\Test\TestCase;
use Variative\Type;

/**
 * ComparisonTest.
 *
 * @internal
 *
 * @coversNothing
 */
class ComparisonTest extends TestCase {
	/**
	 * @param string $equation formatted equation to verify
	 *
	 * @dataProvider comparisonProvider
	 *
	 * @runInSeparateProcess
	 */
	public function testComparison(string $equation): void {
		$result = preg_match(
			'/^([^=<>:]*)(==|!=|<=|>=)([^=<>:]*)(?::(.*))?$/',
			$equation,
			$matches,
		);

		if ($result <= 0) {
			self::fail(sprintf(
				'Invalid equation "%s".',
				$equation,
			));
		}

		$test = [];
		$test['left'] = trim($matches[1]);
		if ($test['left'] == '') {
			$test['left'] = null;
		}
		$test['operator'] = trim($matches[2]);
		$test['right'] = trim($matches[3]);
		if ($test['right'] == '') {
			$test['right'] = null;
		}
		$test['context'] = [];
		$test['conditions'] = false;
		$test['dependencies'] = [];
		if (isset($matches[4])) {
			$test['conditions'] = trim($matches[4]);
			foreach (explode('&', $test['conditions']) as $cond) {
				$cond = trim($cond);

				if (strlen($cond) == 0) {
					continue;
				}

				$data = json_decode($cond, true);
				if (is_array($data)) {
					$test['context'] = $data;

					continue;
				}

				$condResult = preg_match('/^(.+)\s+(?:implements|extends)\s+(.+)$/', $cond, $condMatches);
				if ($condResult > 0) {
					$child = trim($condMatches[1]);
					if (!isset($test['dependencies'][$child])) {
						$test['dependencies'][$child] = [];
					}

					if (!isset($condMatches[2]) || !is_string($condMatches[2])) {
						continue;
					}

					foreach (explode(',', $condMatches[2]) as $parent) {
						$parent = trim($parent);

						if (!in_array($parent, $test['dependencies'][$child], true)) {
							$test['dependencies'][$child][] = $parent;
						}

						if (!isset($test['dependencies'][$parent])) {
							$test['dependencies'][$parent] = [];
						}
					}

					continue;
				}

				$classResult = preg_match('/^([A-Za-z0-9_]+)$/', $cond, $classMatches);
				if ($classResult > 0) {
					$class = trim($classMatches[1]);
					if (!isset($test['dependencies'][$class])) {
						$test['dependencies'][$class] = [];
					}

					continue;
				}

				self::fail(sprintf(
					'Invalid condition "%s".',
					$cond,
				));
			}
		}

		$dependencyCode = [];
		$dependencyCode[] = '{';
		foreach ($test['dependencies'] as $child => $parents) {
			if (count($parents) > 0) {
				$dependencyCode[] = 'interface ' . $child . ' extends ' . implode(', ', $parents) . ' {}';
			} else {
				$dependencyCode[] = 'interface ' . $child . ' {}';
			}
		}

		$dependencyCode[] = '}';
		eval(implode("\n", $dependencyCode));

		try {
			$left = Type::create($test['left'], $test['context']);
		} catch (InvalidArgumentException $exception) {
			self::fail($exception->getMessage());
		}

		try {
			$right = Type::create($test['right'], $test['context']);
		} catch (InvalidArgumentException $exception) {
			self::fail($exception->getMessage());
		}

		if (is_string($test['left'])) {
			// self::assertSame($test['left'], $left->getName());
		}

		if (is_string($test['right'])) {
			// self::assertSame($test['right'], $right->getName());
		}

		$withLogger = $this->logger($withMessages);
		Log::attach($withLogger);
		$withResult = Type::compare($left, $right);
		Log::detach($withLogger);

		$withVariation = match (true) {
			is_null($withResult) => 'invariant',
			$withResult < 0 => 'covariant',
			$withResult > 0 => 'contravariant',
			$withResult == 0 => 'bivariant',
		};

		$fromLogger = $this->logger($fromMessages);
		Log::attach($fromLogger);
		$fromResult = Type::compare($right, $left);
		Log::detach($fromLogger);

		$fromVariation = match (true) {
			is_null($fromResult) => 'invariant',
			$fromResult < 0 => 'covariant',
			$fromResult > 0 => 'contravariant',
			$fromResult == 0 => 'bivariant',
		};

		$withExpectation = match ($test['operator']) {
			'<=' => 'covariant',
			'>=' => 'contravariant',
			'==' => 'bivariant',
			'!=' => 'invariant',
			default => 'noncomparable',
		};

		$fromExpectation = match ($test['operator']) {
			'<=' => 'contravariant',
			'>=' => 'covariant',
			'==' => 'bivariant',
			'!=' => 'invariant',
			default => 'noncomparable',
		};

		$withDetails = '';
		if (self::isDebug() && count($withMessages) > 0) {
			$withDetails = "\n\t" . implode("\n\t", $withMessages);
		}

		$fromDetails = '';
		if (self::isDebug() && count($fromMessages) > 0) {
			$fromDetails = "\n\t" . implode("\n\t", $fromMessages);
		}

		// $withDetails = '';
		// if($withExpectation !== $withVariation) {
		/*
		$debug = Type::debug($left, $right);
		foreach($debug as $trace) {
			$step = [];
			$step['left'] = sprintf(
				'"%s" (%s)',
				$trace['left']->getName(),
				$trace['left']::class,
			);
			$step['right'] = sprintf(
				'"%s" (%s)',
				$trace['right']->getName(),
				$trace['right']::class,
			);
			$step['func'] = $trace['func'];

			if(isset($trace['result'])) {
				$step['result'] = var_export($trace['result'], true);
				if(is_null($trace['result'])) {
					$step['result'] .= ' (invariant)';
				} elseif($trace['result'] < 0) {
					$step['result'] .= ' (covariant)';
				} elseif($trace['result'] > 0) {
					$step['result'] .= ' (contravariant)';
				} else {
					$step['result'] .= ' (bivariant)';
				}
			}

			if(isset($trace['error'])) {
				$step['error'] = sprintf(
					'%s (%s @ %d)',
					$trace['error']->getMessage(),
					$trace['error']->getFile(),
					$trace['error']->getLine(),
				);
			}

			$withDetails .= "\n" . print_r($step, true);
		}
		*/

		// $withDetails = "\n" . print_r(Type::debug($left, $right), true);
		// }

		// $fromDetails = '';
		// if($fromExpectation !== $fromVariation) {
		// $fromDetails = "\n" . print_r(Type::debug($right, $left), true);
		// }

		self::assertSame($withExpectation, $withVariation, sprintf(
			'Failed asserting that "%s" is %s with "%s".%s',
			(string) $left,
			$withExpectation,
			(string) $right,
			$withDetails,
		));

		self::assertSame($fromExpectation, $fromVariation, sprintf(
			'Failed asserting that "%s" is %s from "%s".%s',
			(string) $right,
			$fromExpectation,
			(string) $left,
			$fromDetails,
		));

		/*
		$covarExpectation = in_array($test['operator'], ['<=', '=='], true);
		$contravarExpectation = in_array($test['operator'], ['>=', '=='], true);
		$bivarExpectation = in_array($test['operator'], ['=='], true);
		$invarExpectation = in_array($test['operator'], ['!='], true);

		self::assertSame($covarExpectation, $left->covariantWith($right), sprintf(
			'Failed asserting that "%s" %s covariant with "%s".',
			(string) $left,
			$covarExpectation ? 'is' : 'is not',
			(string) $right,
		));

		self::assertSame($contravarExpectation, $left->contravariantWith($right), sprintf(
			'Failed asserting that "%s" %s contravariant with "%s".',
			(string) $left,
			$contravarExpectation ? 'is' : 'is not',
			(string) $right,
		));

		self::assertSame($bivarExpectation, $left->bivariantWith($right), sprintf(
			'Failed asserting that "%s" %s bivariant with "%s".',
			(string) $left,
			$bivarExpectation ? 'is' : 'is not',
			(string) $right,
		));

		self::assertSame($invarExpectation, $left->invariantWith($right), sprintf(
			'Failed asserting that "%s" %s invariant with "%s".',
			(string) $left,
			$invarExpectation ? 'is' : 'is not',
			(string) $right,
		));

		self::assertSame($covarExpectation, $right->contravariantWith($left), sprintf(
			'Failed asserting that "%s" %s contravariant with "%s".',
			(string) $right,
			$covarExpectation ? 'is' : 'is not',
			(string) $left,
		));

		self::assertSame($contravarExpectation, $right->covariantWith($left), sprintf(
			'Failed asserting that "%s" %s covariant with "%s".',
			(string) $right,
			$contravarExpectation ? 'is' : 'is not',
			(string) $left,
		));

		self::assertSame($bivarExpectation, $right->bivariantWith($left), sprintf(
			'Failed asserting that "%s" %s bivariant with "%s".',
			(string) $right,
			$bivarExpectation ? 'is' : 'is not',
			(string) $left,
		));

		self::assertSame($invarExpectation, $right->invariantWith($left), sprintf(
			'Failed asserting that "%s" %s invariant with "%s".',
			(string) $right,
			$invarExpectation ? 'is' : 'is not',
			(string) $left,
		));
		*/
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public static function comparisonProvider(): array {
		$tests = [];

		try {
			$filesystem = new RecursiveDirectoryIterator(
				__DIR__ . '/spec',
				FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO,
			);
		} catch (UnexpectedValueException $exception) {
			return [];
		}

		$iterator = new RecursiveIteratorIterator($filesystem);

		foreach ($iterator as $path => $info) {
			if (!$info instanceof SplFileInfo || !is_string($path)) {
				continue;
			}

			if ($info->isDir()) {
				continue;
			}

			if (substr($path, -4) != '.txt') {
				continue;
			}

			$offset = strlen(__DIR__ . '/spec/');
			$subpath = substr($path, $offset);

			$contents = file($path);
			if (!is_array($contents)) {
				continue;
			}

			foreach ($contents as $line => $equation) {
				$tests[$subpath . ' @ line ' . ($line + 1)] = [trim($equation)];
			}
		}

		return $tests;
	}
}
