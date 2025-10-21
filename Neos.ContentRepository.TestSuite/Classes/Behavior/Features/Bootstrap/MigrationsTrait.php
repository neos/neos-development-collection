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

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\ContentRepository\NodeMigration\NodeMigrationService;
use Neos\ContentRepository\NodeMigration\NodeMigrationServiceFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * Custom context trait for "Node Migration" related concerns
 */
trait MigrationsTrait
{
    use CRTestSuiteRuntimeVariables;

    private function executeNodeMigration(
        string $sourceWorkspaceName,
        string $targetWorkspaceName,
        string $contentStreamId,
        string $migrationConfigurationYaml,
        bool $publishingOnSuccess,
        bool $force,
    ): void {
        $migrationConfiguration = new MigrationConfiguration(Yaml::parse($migrationConfigurationYaml));
        $command = ExecuteMigration::create(
            $migrationConfiguration,
            WorkspaceName::fromString($sourceWorkspaceName),
            WorkspaceName::fromString($targetWorkspaceName),
        )->withContentStreamId(
            ContentStreamId::fromString($contentStreamId)
        );
        if ($publishingOnSuccess === false) {
            $command = $command->withoutPublishOnSuccess();
        }
        if ($force === true) {
            $command = $command->withoutRequiringConfirmation();
        }

        /** @var NodeMigrationService $nodeMigrationService */
        $nodeMigrationService = $this->getContentRepositoryService(NodeMigrationServiceFactory::createDefault());
        $nodeMigrationService->executeMigration($command);
    }


    /**
     * @When I run the following node migration for workspace :sourceWorkspaceName, creating target workspace :targetWorkspaceName on contentStreamId :contentStreamId, with publishing on success:
     */
    public function iRunTheFollowingNodeMigrationWithTargetWorkspace(string $sourceWorkspaceName, string $targetWorkspaceName, string $contentStreamId, PyStringNode $string): void
    {
        $this->executeNodeMigration(
            sourceWorkspaceName: $sourceWorkspaceName,
            targetWorkspaceName: $targetWorkspaceName,
            contentStreamId: $contentStreamId,
            migrationConfigurationYaml: $string->getRaw(),
            publishingOnSuccess: true,
            force: false
        );
    }

    /**
     * @When I run the following node migration for workspace :sourceWorkspaceName, creating target workspace :targetWorkspaceName on contentStreamId :contentStreamId, with force and publishing on success:
     */
    public function iRunTheFollowingNodeMigrationWithForceWithPublishingOnSuccess(string $sourceWorkspaceName, string $targetWorkspaceName, string $contentStreamId, PyStringNode $string): void
    {
        $this->executeNodeMigration(
            sourceWorkspaceName: $sourceWorkspaceName,
            targetWorkspaceName: $targetWorkspaceName,
            contentStreamId: $contentStreamId,
            migrationConfigurationYaml: $string->getRaw(),
            publishingOnSuccess: false,
            force: true
        );
    }

    /**
     * @When I run the following node migration for workspace :sourceWorkspaceName, creating target workspace :targetWorkspaceName on contentStreamId :contentStreamId, without publishing on success:
     */
    public function iRunTheFollowingNodeMigrationWithoutPublishingOnSuccess(string $sourceWorkspaceName, string $targetWorkspaceName, string $contentStreamId, PyStringNode $string): void
    {
        $this->executeNodeMigration(
            sourceWorkspaceName: $sourceWorkspaceName,
            targetWorkspaceName: $targetWorkspaceName,
            contentStreamId: $contentStreamId,
            migrationConfigurationYaml: $string->getRaw(),
            publishingOnSuccess: false,
            force: false
        );
    }

    /**
     * @When I run the following node migration for workspace :sourceWorkspaceName, creating target workspace :targetWorkspaceName on contentStreamId :contentStreamId and exceptions are caught:
     */
    public function iRunTheFollowingNodeMigrationAndExceptionsAreCaught(string $sourceWorkspaceName, string $targetWorkspaceName, string $contentStreamId, PyStringNode $string): void
    {
        try {
            $this->executeNodeMigration(
                sourceWorkspaceName: $sourceWorkspaceName,
                targetWorkspaceName: $targetWorkspaceName,
                contentStreamId: $contentStreamId,
                migrationConfigurationYaml: $string->getRaw(),
                publishingOnSuccess: true,
                force: false
            );
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
