<?php
namespace Neos\Neos\Tests\Functional\Service\Mapping;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Service\Mapping\NodePropertyConverterService;

/**
 * Functional test case which tests the node property converter
 */
class NodePropertyConverterServiceTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function anArrayOfArrayIsReturnedAsIs()
    {
        $expected = $propertyValue = [[]];

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getPropertyType'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->expects(self::once())
            ->method('getPropertyType')
            ->willReturn('array');

        $node = $this
            ->getMockBuilder(Node::class)
            ->setMethods(['getProperty', 'getNodeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects(self::once())
            ->method('getProperty')
            ->willReturn($propertyValue);
        $node
            ->expects(self::once())
            ->method('getNodeType')
            ->willReturn($nodeType);

        $nodePropertyConverterService = new NodePropertyConverterService();

        $actual = $nodePropertyConverterService->getProperty($node, 'dontcare');

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function arrayOfObjectsWithToStringMethodIsReturnedAsIsUnlessTypeConverterIsProvided()
    {
        $objectWithToStringMethod = new Domain();
        $objectWithToStringMethod->setScheme('http');
        $objectWithToStringMethod->setHostname('neos.io');
        $objectWithToStringMethod->setPort(80);

        $expected = $propertyValue = [$objectWithToStringMethod];

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getPropertyType'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->expects(self::once())
            ->method('getPropertyType')
            ->willReturn('array');

        $node = $this
            ->getMockBuilder(Node::class)
            ->setMethods(['getProperty', 'getNodeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects(self::once())
            ->method('getProperty')
            ->willReturn($propertyValue);
        $node
            ->expects(self::once())
            ->method('getNodeType')
            ->willReturn($nodeType);

        $nodePropertyConverterService = new NodePropertyConverterService();

        $actual = $nodePropertyConverterService->getProperty($node, 'dontcare');

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function arrayOfStringsHasToProvideTypeConverterToBeConvertedToArrayOfStrings()
    {
        $expected = $propertyValue = ['Hello'];

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getPropertyType'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->expects(self::once())
            ->method('getPropertyType')
            ->willReturn('array<string>');

        $node = $this
            ->getMockBuilder(Node::class)
            ->setMethods(['getProperty', 'getNodeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects(self::once())
            ->method('getProperty')
            ->willReturn($propertyValue);
        $node
            ->expects(self::once())
            ->method('getNodeType')
            ->willReturn($nodeType);

        $nodePropertyConverterService = new NodePropertyConverterService();

        $actual = $nodePropertyConverterService->getProperty($node, 'dontcare');

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function complexTypesWithGivenTypeConverterAreConvertedByTypeConverter()
    {
        $propertyValue = $this->getMockForAbstractClass(ImageInterface::class);
        $expected = [
            '__identity' => null,
            '__type' => get_class($propertyValue)
        ];

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getPropertyType'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->expects(self::any())
            ->method('getPropertyType')
            ->willReturn(ImageInterface::class);

        $node = $this
            ->getMockBuilder(Node::class)
            ->setMethods(['getProperty', 'getNodeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects(self::any())
            ->method('getProperty')
            ->willReturn($propertyValue);
        $node
            ->expects(self::any())
            ->method('getNodeType')
            ->willReturn($nodeType);

        $nodePropertyConverterService = new NodePropertyConverterService();

        $actual = $nodePropertyConverterService->getProperty($node, 'dontcare');

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function jsonSerializedAbleTypesAreDirectlySerialized()
    {
        $voClassName = 'Value' . md5(uniqid(mt_rand(), true));
        eval('class ' . $voClassName . ' implements \JsonSerializable' . <<<'PHP'
        {
            public function __construct(
                public \Psr\Http\Message\UriInterface $uri
            ) {
            }

            public static function fromArray(array $array): self
            {
                return new self(
                    new \GuzzleHttp\Psr7\Uri($array['uri'])
                );
            }

            public function jsonSerialize(): array
            {
                return [
                    'uri' => $this->uri->__toString()
                ];
            }
        }
        PHP);

        $propertyValue = new $voClassName(new Uri('localhost://foo.html'));
        $expected = '{"uri":"localhost:\\/\\/foo.html"}';

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getPropertyType'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->expects(self::any())
            ->method('getPropertyType')
            ->willReturn(ImageInterface::class);

        $node = $this
            ->getMockBuilder(Node::class)
            ->setMethods(['getProperty', 'getNodeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $node
            ->expects(self::any())
            ->method('getProperty')
            ->willReturn($propertyValue);
        $node
            ->expects(self::any())
            ->method('getNodeType')
            ->willReturn($nodeType);

        $nodePropertyConverterService = new NodePropertyConverterService();

        $actual = $nodePropertyConverterService->getProperty($node, 'dontcare');

        self::assertEquals($expected, json_encode($actual));
    }
}
