<?php

use Phan\Issue;

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 */
return [

	'target_php_version' => '8.2',

	'directory_list' => [
		'src',
		'test',
		'vendor/psr/log',
		'vendor/phpunit/phpunit/src'
	],

	'suppress_issue_types' => [
		'PhanTypeMismatchDeclaredParam', // creates issues with phpstan imported types
	],

	'minimum_severity' => Issue::SEVERITY_NORMAL,

	'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',

	'exclude_analysis_directory_list' => [
		'vendor/',
	],
];
