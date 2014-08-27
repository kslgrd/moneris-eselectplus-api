<?php
/**
 * To run these tests:
 *
 * - Download simpletest (http://www.simpletest.org), and put it alongside the examples, lib, and tests folders.
 * - Pop open a terminal
 * - Navigate to the tests folder
 * - run: php all_tests.php (or php-mamp all_tests.php if you're using MAMP)
 *
 * I guess you can also run these in a GUI... but I never even tried so I don't know if it works.
 */
if (! defined('ROOT')) {
	define('ROOT', dirname(dirname(__FILE__)));
}
require_once ROOT . '/simpletest/autorun.php';
require_once ROOT . '/lib/Moneris.php';

class AllTests extends TestSuite
{
	function __construct() {
		parent::__construct();
		$this->addFile('basic.php');
	}
}