<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Migration\Domain\Model\MigrationConfiguration;
use Neos\EventSourcedContentRepository\Domain\Context\Migration\NodeMigrationService;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Custom context trait for "Node Migration" related concerns
 */
trait MigrationsTrait
{
    protected NodeMigrationService $nodeMigrationService;

    /**
     * @return ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function setupMigrationsTrait(): void
    {
        $this->nodeMigrationService = $this->getObjectManager()->get(NodeMigrationService::class);
    }
    /**
     * @When I run the following node migration for workspace :workspaceName, creating content stream :contentStream:
     */
    public function iRunTheFollowingNodeMigration(string $workspaceName, string $contentStream, PyStringNode $string)
    {
        $migrationConfiguration = new MigrationConfiguration(Yaml::parse($string->getRaw()));
        $this->nodeMigrationService->execute($migrationConfiguration, new WorkspaceName($workspaceName), ContentStreamIdentifier::fromString($contentStream));
    }
}
