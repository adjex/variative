<?php

/**
 * This file is part of Variative.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Variative;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use WeakMap;

/**
 * Log
 *
 * Used for debugging type resolution.
 */
final class Log {
	/** @var WeakMap<LoggerInterface, bool> $loggers */
	private static WeakMap $loggers;

	private function __construct() {
		// disabled constructor
	}

	/**
	 * Attach a logger.
	 *
	 * @param LoggerInterface $logger The logger to attach.
	 *
	 * @return void
	 */
	public static function attach(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			self::$loggers = new WeakMap();
		}

		self::$loggers[$logger] = true;
	}

	/**
	 * Detach a logger.
	 *
	 * @param LoggerInterface $logger The logger to detach.
	 *
	 * @return void
	 */
	public static function detach(LoggerInterface $logger): void {
		if (!isset(self::$loggers)) {
			return;
		}

		unset(self::$loggers[$logger]);
	}

	/**
	 * Log an emergency event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function emergency(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Log an alert event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function alert(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Log a critical event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function critical(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Log an error event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function error(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * Log a warning event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function warning(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Log a notice event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function notice(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Log an info event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function info(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::INFO, $message, $context);
	}

	/**
	 * Log a debug event.
	 *
	 * @param string|Stringable $message The event message.
	 * @param mixed[]           $context The event context.
	 *
	 * @return void
	 */
	public static function debug(string|Stringable $message, array $context = []): void {
		self::log(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * @param string            $level
	 * @param string|Stringable $message
	 * @param mixed[]           $context
	 *
	 * @return void
	 */
	private static function log(string $level, string|Stringable $message, array $context = []): void {
		if (!isset(self::$loggers)) {
			return;
		}

		foreach (self::$loggers as $logger => $valid) {
			try {
				$logger->log($level, $message, $context);
			} catch (InvalidArgumentException $exception) {
				// impossible, only called with valid log level constants
			}
		}
	}
}
