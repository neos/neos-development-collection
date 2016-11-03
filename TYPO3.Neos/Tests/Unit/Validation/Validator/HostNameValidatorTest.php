<?php
namespace TYPO3\Neos\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Validation\Validator\HostnameValidator;

/**
 * Testcase for the HostNameValidator
 *
 */
class HostNameValidatorTest extends UnitTestCase
{
    public function hostNameDataProvider()
    {
        return array(
            // correct names
            'hostname'                            => array('hostName' => 'localhost', 'valid' => true),
            'www.host.de'                        => array('hostName' => 'www.host.de', 'valid' => true),
            'www.host.travel'                    => array('hostName' => 'www.host.travel', 'valid' => true),
            'digits in local nodes are allowed'    => array('hostName' => '4you.test.de', 'valid' => true),

            // incorrect names
            'part longer than 63 characters'    => array('hostName' => 'www.' . str_repeat('abcd', 16) . '.de', 'valid' => false),
            'name longer than 253 characters'    => array('hostName' => str_repeat('abcd.', 50) . 'neos', 'valid' => false),
            'two consecutive dots'                => array('hostName' => 'www..de', 'valid' => false),
            'node does not start with -'        => array('hostName' => '-test.de', 'valid' => false),
            'node does not end with -'            => array('hostName' => 'test-.de', 'valid' => false),
            'singleNode does not start with -'    => array('hostName' => '-localhost', 'valid' => false),
            'singleNode does not end with -'    => array('hostName' => 'localhost-', 'valid' => false),
            'tld consist of min 2 chars'        => array('hostName' => 'test.x', 'valid' => false),
            'tld should not start with -'        => array('hostName' => 'test.-de', 'valid' => false),
            'tld should not end with -'            => array('hostName' => 'test.de-', 'valid' => false),
            'tld should not contain digits'        => array('hostName' => 'you.test.42', 'valid' => false),
        );
    }

    /**
     * @test
     * @dataProvider hostNameDataProvider
     */
    public function validate($hostName, $valid)
    {
        $validator = new HostnameValidator();

        $actual = !$validator->validate($hostName)->hasErrors();
        $this->assertEquals($valid, $actual, sprintf('The validator returned %s but should return %s.', $actual === true ? 'TRUE' : 'FALSE', $valid === true ? 'TRUE' : 'FALSE'));
    }
}
