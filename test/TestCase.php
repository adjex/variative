<?php

/*
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Abstract Test Case.
 */
abstract class TestCase extends BaseTestCase {
	/**
	 * Mark test as incomplete.
	 */
	protected function toDo(): void {
		$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

		$message = sprintf('To-Do: %s', $caller['function']);
		if (isset($caller['class'])) {
			$message = sprintf('To-Do: %s::%s', $caller['class'], $caller['function']);
		}

		self::markTestIncomplete($message);
	}

	/**
	 * Check for verbose mode.
	 *
	 * @return bool true if verbose flag is set
	 */
	protected function isVerbose(): bool {
		if (self::isDebug()) {
			return true;
		}

		if (in_array('--verbose', $_SERVER['argv'], true)) {
			return true;
		}

		return false;
	}

	/**
	 * Check for debug mode.
	 *
	 * @return bool true if debug flag is set
	 */
	protected function isDebug(): bool {
		if (in_array('--debug', $_SERVER['argv'], true)) {
			return true;
		}

		return false;
	}

	/**
	 * Create a simple logger.
	 *
	 * @param string[]|null $messages optional reference to an array to store messages in
	 * @param string        $level    log level (from LogLevel)
	 */
	protected function logger(?array &$messages = null, string $level = LogLevel::DEBUG): LoggerInterface {
		return new class($messages, $level) extends AbstractLogger {
			/** @var string[] */
			private array $messages;

			private int $level;

			/**
			 * Create a new logger.
			 *
			 * @param string[]|null $messages array reference to store message in
			 * @param string        $level    log level
			 */
			public function __construct(?array &$messages, string $level = LogLevel::DEBUG) {
				if (is_null($messages)) {
					$messages = [];
				}
				$this->messages = &$messages;
				$this->level = $this->levelToInteger($level);
			}

			/**
			 * Get the messages.
			 *
			 * @return string[] $messages
			 */
			public function messages(): array {
				return $this->messages;
			}

			public function log(mixed $level, string|Stringable $message, array $context = []): void {
				if (!is_string($level)) {
					return;
				}

				$levelInt = $this->levelToInteger($level);
				if ($levelInt < $this->level) {
					return;
				}

				$this->messages[] = sprintf(
					'[%s] %s',
					strtoupper($level),
					$message,
				);
			}

			private function levelToInteger(string $level): int {
				switch ($level) {
					case LogLevel::EMERGENCY:
						return 8;
					case LogLevel::ALERT:
						return 7;
					case LogLevel::CRITICAL:
						return 6;
					case LogLevel::ERROR:
						return 5;
					case LogLevel::WARNING:
						return 4;
					case LogLevel::NOTICE:
						return 3;
					case LogLevel::INFO:
						return 2;
					case LogLevel::DEBUG:
						return 1;
					default:
						return 0;
				}
			}
		};
	}
}
