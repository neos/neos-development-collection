<?php
namespace Neos\Neos\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Validation\Validator\HostnameValidator;

/**
 * Testcase for the HostNameValidator
 *
 */
class HostNameValidatorTest extends UnitTestCase
{
    public function hostNameDataProvider()
    {
        return [
            // correct names
            'hostname'                            => ['hostName' => 'localhost', 'valid' => true],
            'www.host.de'                        => ['hostName' => 'www.host.de', 'valid' => true],
            'www.host.travel'                    => ['hostName' => 'www.host.travel', 'valid' => true],
            'digits in local nodes are allowed'    => ['hostName' => '4you.test.de', 'valid' => true],

            // incorrect names
            'part longer than 63 characters'    => ['hostName' => 'www.' . str_repeat('abcd', 16) . '.de', 'valid' => false],
            'name longer than 253 characters'    => ['hostName' => str_repeat('abcd.', 50) . 'neos', 'valid' => false],
            'two consecutive dots'                => ['hostName' => 'www..de', 'valid' => false],
            'node does not start with -'        => ['hostName' => '-test.de', 'valid' => false],
            'node does not end with -'            => ['hostName' => 'test-.de', 'valid' => false],
            'singleNode does not start with -'    => ['hostName' => '-localhost', 'valid' => false],
            'singleNode does not end with -'    => ['hostName' => 'localhost-', 'valid' => false],
            'tld consist of min 2 chars'        => ['hostName' => 'test.x', 'valid' => false],
            'tld should not start with -'        => ['hostName' => 'test.-de', 'valid' => false],
            'tld should not end with -'            => ['hostName' => 'test.de-', 'valid' => false],
            'tld should not contain digits'        => ['hostName' => 'you.test.42', 'valid' => false],
        ];
    }

    /**
     * @test
     * @dataProvider hostNameDataProvider
     */
    public function validate($hostName, $valid)
    {
        $validator = new HostnameValidator();

        $actual = !$validator->validate($hostName)->hasErrors();
        self::assertEquals($valid, $actual, sprintf('The validator returned %s but should return %s.', $actual === true ? 'true' : 'false', $valid === true ? 'true' : 'false'));
    }
}
