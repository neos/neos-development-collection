<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\NodeCreationHandler;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;

/**
 * NodeTypePostprocessorInterface
 */
interface NodeCreationHandlerInterface
{
    /**
     * Do something with the newly created node
     *
     * @param TraversableNodeInterface $node The newly created node
     * @param array $data incoming data from the creationDialog
     * @return void
     */
    public function handle(TraversableNodeInterface $node, array $data);
}
