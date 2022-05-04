<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\ContentRepository\Projection\Content\ProjectionIntegrityViolationDetectionRunner;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for projection integrity violation detection related concerns
 */
trait ProjectionIntegrityViolationDetectionTrait
{
    protected ProjectionIntegrityViolationDetectionRunner $projectionIntegrityViolationDetectionRunner;

    protected Result $lastIntegrityViolationDetectionResult;

    abstract protected function getObjectManager(): ObjectManagerInterface;

    protected function setupProjectionIntegrityViolationDetectionTrait(): void
    {
        $this->projectionIntegrityViolationDetectionRunner = $this->getObjectManager()->get(ProjectionIntegrityViolationDetectionRunner::class);
    }

    /**
     * @When /^I run integrity violation detection$/
     */
    public function iRunIntegrityViolationDetection(): void
    {
        $this->lastIntegrityViolationDetectionResult = $this->projectionIntegrityViolationDetectionRunner->run();
    }

    /**
     * @Then /^I expect the integrity violation detection result to contain exactly (\d+) errors?$/
     * @param int $expectedNumberOfErrors
     */
    public function iExpectTheIntegrityViolationDetectionResultToContainExactlyNErrors(int $expectedNumberOfErrors): void
    {
        Assert::assertSame(
            $expectedNumberOfErrors,
            count($this->lastIntegrityViolationDetectionResult->getErrors()),
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
        $error = $this->lastIntegrityViolationDetectionResult->getErrors()[$errorNumber-1];
        Assert::assertSame(
            $expectedErrorCode,
            $error->getCode()
        );
    }
}
