<?php

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentStreamForkBufferService;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentStreamForkBufferServiceFactory;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use PHPUnit\Framework\Assert;

trait ForkBufferTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    public function getContentStreamForkBufferService(): ContentStreamForkBufferService
    {
        return $this->getContentRepositoryService(
            new ContentStreamForkBufferServiceFactory(
                $this->getObject(Connection::class)
            )
        );
    }

    /**
     * @Then I expect :number buffered forks for content stream :contentStreamId
     */
    public function iExpectNumberOfBufferedForksForContentStreamId(int $number, string $contentStreamId): void
    {
        Assert::assertSame(
            $number,
            $this->getContentStreamForkBufferService()->countBufferForksByContentStreamId(
                ContentStreamId::fromString($contentStreamId)
            )
        );
    }

    /**
     * @When I create :number buffered forks for content stream :contentStreamId
     */
    public function iCreateNumberOfBufferedForksForContentStreamId(int $number, string $contentStreamId): void
    {
        $this->getContentStreamForkBufferService()->preForkContentStreamId(
            ContentStreamId::fromString($contentStreamId),
            $number
        );
    }
}
