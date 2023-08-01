<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeClock;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;

/**
 * The node creation trait for behavioral tests
 */
trait CRTestSuiteRuntimeVariables
{

    protected ?ContentStreamId $currentContentStreamId = null;

    protected ?DimensionSpacePoint $currentDimensionSpacePoint = null;

    protected ?VisibilityConstraints $currentVisibilityConstraints = null;

    protected ?NodeAggregateId $currentRootNodeAggregateId = null;

    protected ?CommandResult $lastCommandOrEventResult = null;

    protected ?\Exception $lastCommandException = null;

    /**
     * @Given /^I am user identified by "([^"]*)"$/
     */
    public function iAmUserIdentifiedBy(string $userId): void
    {
        FakeUserIdProvider::setUserId(UserId::fromString($userId));
    }

    /**
     * @When the current date and time is :timestamp
     */
    public function theCurrentDateAndTimeIs(string $timestamp): void
    {
        FakeClock::setNow(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestamp));
    }

    /**
     * @Given /^I am in content stream "([^"]*)"$/
     */
    public function iAmInContentStream(string $contentStreamId): void
    {
        $this->currentContentStreamId = ContentStreamId::fromString($contentStreamId);
    }

    /**
     * @Given /^I am in dimension space point (.*)$/
     */
    public function iAmInDimensionSpacePoint(string $dimensionSpacePoint): void
    {
        $this->currentDimensionSpacePoint = DimensionSpacePoint::fromJsonString($dimensionSpacePoint);
    }

    /**
     * @Given /^I am in content stream "([^"]*)" and dimension space point (.*)$/
     */
    public function iAmInContentStreamAndDimensionSpacePoint(string $contentStreamId, string $dimensionSpacePoint): void
    {
        $this->iAmInContentStream($contentStreamId);
        $this->iAmInDimensionSpacePoint($dimensionSpacePoint);
    }

    /**
     * @When /^VisibilityConstraints are set to "(withoutRestrictions|frontend)"$/
     */
    public function visibilityConstraintsAreSetTo(string $restrictionType): void
    {
        $this->currentVisibilityConstraints = match ($restrictionType) {
            'withoutRestrictions' => VisibilityConstraints::withoutRestrictions(),
            'frontend' => VisibilityConstraints::frontend(),
            default => throw new \InvalidArgumentException('Visibility constraint "' . $restrictionType . '" not supported.'),
        };
    }
}
