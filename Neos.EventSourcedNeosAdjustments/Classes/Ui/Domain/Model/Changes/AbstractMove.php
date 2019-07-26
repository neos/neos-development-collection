<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

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

abstract class AbstractMove extends AbstractStructuralChange
{
    /**
     * Perform finish tasks - needs to be called from inheriting class on `apply`
     *
     * @param TraversableNodeInterface $node
     * @return void
     */
    protected function finish(TraversableNodeInterface $node)
    {
        //$removeNode = new RemoveNode();
        //$removeNode->setNode($node);

        //$this->feedbackCollection->add($removeNode);

        // $this->getSubject() is the moved node at the NEW location!
        parent::finish($this->getSubject());
    }
}
