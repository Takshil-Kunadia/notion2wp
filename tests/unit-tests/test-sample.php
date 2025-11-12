<?php
/**
 * Sample unit test file.
 *
 * @package Notion2WP
 */

use PHPUnit\Framework\TestCase;

/**
 * Sample test case.
 */
class SampleTest extends TestCase {

	/**
	 * Test addition.
	 */
	public function testAddition() {
		$this->assertEquals( 2, 1 + 1 );
	}

	/**
	 * Test string contains.
	 */
	public function testString() {
		$this->assertStringContainsString( 'world', 'hello world' );
	}
}
