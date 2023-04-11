<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Annotations as Flow;

final class StructureAdjustmentsCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function detectCommand(string $nodeType = null, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $structureAdjustmentService = $this->contentRepositoryRegistry->getService($contentRepositoryId, new StructureAdjustmentServiceFactory());

        if ($nodeType !== null) {
            $errors = $structureAdjustmentService->findAdjustmentsForNodeType(
                NodeTypeName::fromString($nodeType)
            );
        } else {
            $errors = $structureAdjustmentService->findAllAdjustments();
        }

        $this->printErrors($errors);
    }

    public function fixCommand(string $nodeType = null, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $structureAdjustmentService = $this->contentRepositoryRegistry->getService($contentRepositoryId, new StructureAdjustmentServiceFactory());

        if ($nodeType !== null) {
            $errors = $structureAdjustmentService->findAdjustmentsForNodeType(
                NodeTypeName::fromString($nodeType)
            );
        } else {
            $errors = $structureAdjustmentService->findAllAdjustments();
        }

        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
            $structureAdjustmentService->fixError($error);
        }
        $this->outputLine('Fixed all.');
    }

    /**
     * @param \Generator<int,StructureAdjustment> $errors
     */
    private function printErrors(\Generator $errors): void
    {
        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
        }
    }
}
