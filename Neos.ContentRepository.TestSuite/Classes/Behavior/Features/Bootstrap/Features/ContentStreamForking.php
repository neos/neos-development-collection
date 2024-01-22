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

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;

/**
 * The content stream forking feature trait for behavioral tests
 */
trait ContentStreamForking
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command ForkContentStream is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandForkContentStreamIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $sourceContentStreamId = isset($commandArguments['sourceContentStreamId'])
            ? ContentStreamId::fromString($commandArguments['sourceContentStreamId'])
            : $this->currentContentStreamId;

        $command = ForkContentStream::create(
            ContentStreamId::fromString($commandArguments['contentStreamId']),
            $sourceContentStreamId,
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }
}
