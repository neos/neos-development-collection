<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Intermediary\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Intermediary\StructureAdjustment\Dto\StructureAdjustment;
use Neos\ContentRepository\Intermediary\StructureAdjustment\StructureAdjustmentService;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Annotations as Flow;

final class StructureAdjustmentsCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var StructureAdjustmentService
     */
    protected $structureAdjustmentService;

    public function detectCommand(string $nodeType = null): void
    {
        if ($nodeType !== null) {
            $errors = $this->structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeType));
        } else {
            $errors = $this->structureAdjustmentService->findAllAdjustments();
        }

        $this->printErrors($errors);
    }

    public function fixCommand(string $nodeType = null): void
    {
        if ($nodeType !== null) {
            $errors = $this->structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeType));
        } else {
            $errors = $this->structureAdjustmentService->findAllAdjustments();
        }

        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
            $error->fix()->blockUntilProjectionsAreUpToDate();
        }
        $this->outputLine('Fixed all.');
    }

    private function printErrors(\Generator $errors)
    {
        foreach ($errors as $error) {
            assert($error instanceof StructureAdjustment);
            $this->outputLine($error->render());
        }
    }
}
