<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectionRunnerFactoryInterface;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use PHPUnit\Framework\Assert;

trait ProjectionIntegrityViolationDetectionTrait
{
    protected Result $lastIntegrityViolationDetectionResult;

    /**
     * @When /^I run integrity violation detection$/
     */
    public function iRunIntegrityViolationDetection(): void
    {
        $projectionIntegrityViolationDetectionRunner = $this->getContentRepositoryService(
            $this->getObject(ProjectionIntegrityViolationDetectionRunnerFactoryInterface::class)
        );
        $this->lastIntegrityViolationDetectionResult = $projectionIntegrityViolationDetectionRunner->run();
    }

    /**
     * @Then /^I expect the integrity violation detection result to contain exactly (\d+) errors?$/
     * @param int $expectedNumberOfErrors
     */
    public function iExpectTheIntegrityViolationDetectionResultToContainExactlyNErrors(int $expectedNumberOfErrors): void
    {
        Assert::assertCount(
            $expectedNumberOfErrors,
            $this->lastIntegrityViolationDetectionResult->getErrors(),
            'Errors were: ' . implode(', ', array_map(fn (Error $e) => $e->render(), $this->lastIntegrityViolationDetectionResult->getErrors()))
        );
    }

    /**
     * @Then /^I expect integrity violation detection result error number (\d+) to have code (\d+)$/
     * @param int $errorNumber
     * @param int $expectedErrorCode
     */
    public function iExpectIntegrityViolationDetectionResultErrorNumberNToHaveCodeX(int $errorNumber, int $expectedErrorCode): void
    {
        /** @var Error $error */
        $error = $this->lastIntegrityViolationDetectionResult->getErrors()[$errorNumber - 1];
        Assert::assertSame(
            $expectedErrorCode,
            $error->getCode()
        );
    }
}
