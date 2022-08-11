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

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\NodeMigration\NodeMigrationServiceFactory;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepository\NodeMigration\NodeMigrationService;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Custom context trait for "Node Migration" related concerns
 */
trait MigrationsTrait
{
    protected NodeMigrationService $nodeMigrationService;

    abstract protected function getObjectManager(): ObjectManagerInterface;
    abstract protected function getContentRepositoryIdentifier(): ContentRepositoryIdentifier;
    abstract protected function getContentRepositoryRegistry(): ContentRepositoryRegistry;

    protected function setupMigrationsTrait(): void
    {
        $this->nodeMigrationService = $this->getContentRepositoryRegistry()->getService($this->getContentRepositoryIdentifier(), new NodeMigrationServiceFactory());
    }
    /**
     * @When I run the following node migration for workspace :workspaceName, creating content streams :contentStreams:
     */
    public function iRunTheFollowingNodeMigration(string $workspaceName, string $contentStreams, PyStringNode $string)
    {
        $migrationConfiguration = new MigrationConfiguration(Yaml::parse($string->getRaw()));
        $contentStreamIdentifiers = array_map(fn (string $cs) => ContentStreamIdentifier::fromString($cs), explode(',', $contentStreams));
        $command = new ExecuteMigration($migrationConfiguration, WorkspaceName::fromString($workspaceName), $contentStreamIdentifiers);
        $this->nodeMigrationService->executeMigration($command);
    }

    /**
     * @When I run the following node migration for workspace :workspaceName, creating content streams :contentStreams and exceptions are caught:
     */
    public function iRunTheFollowingNodeMigrationAndExceptionsAreCaught(string $workspaceName, string $contentStreams, PyStringNode $string)
    {
        try {
            $this->iRunTheFollowingNodeMigration($workspaceName, $contentStreams, $string);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
