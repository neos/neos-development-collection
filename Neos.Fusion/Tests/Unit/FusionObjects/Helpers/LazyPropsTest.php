<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects\Helpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\Fusion\FusionObjects\ValueImplementation;
use Neos\Fusion\FusionObjects\Helpers\LazyProps;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;

class LazyPropsTest extends UnitTestCase
{
    #[Test]
    public function jsonEncodeSerializesAllProps()
    {
        /** @var Runtime $mockRuntime */
        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $mockRuntime->expects($this->any())->method('evaluate')->withAnyParameters()->willReturnCallback(function ($path) {
            return $path;
        });

        $fusionObject = new ValueImplementation($mockRuntime, 'test/path', 'Value');
        $lazyProps = new LazyProps($fusionObject, 'test/path', $mockRuntime, ['foo', 'bar'], ['value' => 42]);

        $serializedProps = json_encode($lazyProps);
        $this->assertEquals('{"foo":"test\/path\/foo","bar":"test\/path\/bar"}', $serializedProps);
    }
}
