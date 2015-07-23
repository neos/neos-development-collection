<?php
namespace TYPO3\Neos\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Validation\Validator\HostnameValidator;

/**
 * Testcase for the HostNameValidator
 *
 */
class HostNameValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {


	public function hostNameDataProvider() {
		return array(
			// correct names
			'hostname'							=> array('hostName' => 'localhost', 'valid' => TRUE),
			'www.host.de'						=> array('hostName' => 'www.host.de', 'valid' => TRUE),
			'www.host.travel'					=> array('hostName' => 'www.host.travel', 'valid' => TRUE),
			'digits in local nodes are allowed'	=> array('hostName' => '4you.test.de', 'valid' => TRUE),

			// incorrect names
			'part longer than 63 characters'	=> array('hostName' => 'www.' . str_repeat('abcd', 16) . '.de', 'valid' => FALSE),
			'name longer than 253 characters'	=> array('hostName' => str_repeat('abcd.', 50) . 'neos', 'valid' => FALSE),
			'two consecutive dots'				=> array('hostName' => 'www..de', 'valid' => FALSE),
			'node does not start with -'		=> array('hostName' => '-test.de', 'valid' => FALSE),
			'node does not end with -'			=> array('hostName' => 'test-.de', 'valid' => FALSE),
			'singleNode does not start with -'	=> array('hostName' => '-localhost', 'valid' => FALSE),
			'singleNode does not end with -'	=> array('hostName' => 'localhost-', 'valid' => FALSE),
			'tld consist of min 2 chars'		=> array('hostName' => 'test.x', 'valid' => FALSE),
			'tld should not start with -'		=> array('hostName' => 'test.-de', 'valid' => FALSE),
			'tld should not end with -'			=> array('hostName' => 'test.de-', 'valid' => FALSE),
			'tld should not contain digits'		=> array('hostName' => 'you.test.42', 'valid' => FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider hostNameDataProvider
	 */
	public function validate($hostName, $valid) {
		$validator = new HostnameValidator();

		$actual = !$validator->validate($hostName)->hasErrors();
		$this->assertEquals($valid, $actual, sprintf('The validator returned %s but should return %s.', $actual === TRUE ? 'TRUE' : 'FALSE', $valid === TRUE ? 'TRUE' : 'FALSE'));
	}
}