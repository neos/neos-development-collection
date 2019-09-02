<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\CaseImplementation;

/**
 * Testcase for the Case object
 */
class CaseImplementationTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function ignoredPropertiesShouldNotBeUsedAsMatcher()
    {
        $path = 'page/body/content/main';
        $ignoredProperties = ['nodePath'];

        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) use ($path, $ignoredProperties) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            switch ($relativePath) {
                case '__meta/ignoreProperties':
                    return $ignoredProperties;
            }
            return ObjectAccess::getProperty($that, $relativePath, true);
        }));

        $fusionObjectName = 'Neos.Neos:PrimaryContent';
        $renderer = new CaseImplementation($mockRuntime, $path, $fusionObjectName);
        $renderer->setIgnoreProperties($ignoredProperties);

        $renderer['nodePath'] = 'main';
        $renderer['default'] = [
            'condition' => 'true'
        ];

        $mockRuntime->expects(self::once())->method('render')->with('page/body/content/main/default<Neos.Fusion:Matcher>')->will(self::returnValue('rendered matcher'));

        $result = $renderer->evaluate();

        self::assertEquals('rendered matcher', $result);
    }
}
