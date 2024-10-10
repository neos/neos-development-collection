<?php

declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use PHPUnit\Framework\Assert;

/**
 * Trait that can be used to test for exceptions
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait ExceptionsTrait
{
    private \Exception|null $lastCaughtException = null;

    private function tryCatchingExceptions(\Closure $callback): mixed
    {
        if ($this->lastCaughtException !== null) {
            throw new \RuntimeException(sprintf('Can\'t execute new commands before catching previous exception: %s', $this->lastCaughtException->getMessage()), 1728464381, $this->lastCaughtException);
        }
        try {
            return $callback();
        } catch (\Exception $exception) {
            $this->lastCaughtException = $exception;
            return null;
        }
    }

    /**
     * @Then an exception :exceptionMessage should be thrown
     */
    public function anExceptionShouldBeThrown(string $exceptionMessage): void
    {
        Assert::assertNotNull($this->lastCaughtException, 'Expected an exception but none was thrown');
        Assert::assertSame($exceptionMessage, $this->lastCaughtException->getMessage());
        $this->lastCaughtException = null;
    }

    /**
     * @BeforeScenario
     * @AfterScenario
     */
    public function afterScenarioExceptionsTrait(): void
    {
        if ($this->lastCaughtException !== null) {
            throw new \RuntimeException(sprintf('Previous exception was not handled: %s', $this->lastCaughtException->getMessage()), 1728464379, $this->lastCaughtException);
        }
    }
}
