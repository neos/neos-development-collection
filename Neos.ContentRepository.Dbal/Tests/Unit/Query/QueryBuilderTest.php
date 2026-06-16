<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Tests\Unit\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Neos\ContentRepository\Dbal\Query\AmbiguousParametersGiven;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private QueryBuilder $dbal;

    public function setUp(): void
    {
        $this->dbal = QueryBuilder::createForConnection(
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock()
        );
    }

    /** @test */
    public function mergeParametersOnEmpty(): void
    {
        self::assertEmpty($this->dbal->getParameters());
        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'abc'),
            Parameter::stringArray('myStringArray', ['abc']),
        ));

        self::assertSame(['myString' => 'abc', 'myStringArray' => ['abc']], $this->dbal->getParameters());
        self::assertSame(['myString' => ParameterType::STRING, 'myStringArray' => ArrayParameterType::STRING], $this->dbal->getParameterTypes());
    }

    /** @test */
    public function mergeParametersOnSingle(): void
    {
        $this->dbal->setParameter('initialString', 'initialValue');

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'abc'),
            Parameter::stringArray('myStringArray', ['abc']),
        ));

        self::assertSame(['initialString' => 'initialValue', 'myString' => 'abc', 'myStringArray' => ['abc']], $this->dbal->getParameters());
        self::assertSame(['initialString' => ParameterType::STRING, 'myString' => ParameterType::STRING, 'myStringArray' => ArrayParameterType::STRING], $this->dbal->getParameterTypes());
    }

    /** @test */
    public function mergeParametersWithSameDuplicates(): void
    {
        $this->dbal->setParameter('myString', 'abc');
        $this->dbal->setParameter('myStringArray', ['abc'], ArrayParameterType::STRING);

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'abc'),
            Parameter::stringArray('myStringArray', ['abc']),
        ));

        self::assertSame(['myString' => 'abc', 'myStringArray' => ['abc']], $this->dbal->getParameters());
        self::assertSame(['myString' => ParameterType::STRING, 'myStringArray' => ArrayParameterType::STRING], $this->dbal->getParameterTypes());
    }

    /** @test */
    public function mergeParametersWithSameNameAndDifferentValue(): void
    {
        $this->dbal->setParameter('myString', 'abc');

        $this->expectExceptionObject(AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithValue(
            'myString',
            'abc',
            'another-value'
        ));

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'another-value')
        ));
    }

    /** @test */
    public function mergeParametersWithSameNameAndDifferentType(): void
    {
        $this->dbal->setParameter('stringOrInteger', 0, ParameterType::STRING);

        $this->expectExceptionObject(AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithType(
            'stringOrInteger',
            ParameterType::STRING,
            ParameterType::INTEGER,
        ));

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::integer('stringOrInteger', 0)
        ));
    }

    /** @test */
    public function mergeParametersWithSameDuplicatesWithinParameters(): void
    {
        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'abc'),
            Parameter::stringArray('myStringArray', ['abc']),
            Parameter::string('myString', 'abc'),
            Parameter::stringArray('myStringArray', ['abc']),
        ));

        self::assertSame(['myString' => 'abc', 'myStringArray' => ['abc']], $this->dbal->getParameters());
        self::assertSame(['myString' => ParameterType::STRING, 'myStringArray' => ArrayParameterType::STRING], $this->dbal->getParameterTypes());
    }

    /** @test */
    public function mergeParametersWithSameNameAndDifferentValueWithinParameters(): void
    {
        $this->expectExceptionObject(AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithValue(
            'myString',
            'abc',
            'another-value'
        ));

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('myString', 'abc'),
            Parameter::string('myString', 'another-value')
        ));
    }

    /** @test */
    public function mergeParametersWithSameNameAndDifferentTypeWithinParameters(): void
    {
        $this->expectExceptionObject(AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithType(
            'stringOrInteger',
            ParameterType::STRING,
            ParameterType::INTEGER,
        ));

        $this->dbal->mergeParameters(Parameters::create(
            Parameter::string('stringOrInteger', '0'),
            Parameter::integer('stringOrInteger', 0),
        ));
    }
}
