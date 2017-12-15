<?php

namespace Neos\Neos\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;

/**
 * Node Uri Path
 *
 * @api
 */
class NodeUriPath
{
    /**
     * @var ObjectManager
     * @Flow\Inject
     */
    protected $entityManager;

    /**
     * @var NodeDataRepository
     * @Flow\Inject
     */
    protected $nodeDataRepository;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @param NodeInterface $node
     */
    public function __construct(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * @return string
     * @throws NodeException
     */
    public function __toString()
    {
        return $this->find();
    }

    /**
     * @return string
     * @throws NodeException
     */
    public function find()
    {
        switch ($this->entityManager->getConnection()->getDatabasePlatform()->getName()) {
            case 'postgresql':
                $value = $this->findWithPostgres();
                break;
            default:
                $value = $this->findWithFlowQuery();
        }
        return $value;
    }

    /**
     * @return string|null
     * @throws NodeException
     */
    protected function findWithPostgres()
    {
        $possibleUriPathSegment = $initialUriPathSegment = !$this->node->hasProperty('uriPathSegment') ? $this->node->getName() : $this->node->getProperty('uriPathSegment');
        $i = 1;
        while ($this->nodeDataRepository->isPropertyUnique('uriPathSegment', $possibleUriPathSegment, $this->node) === true) {
            $possibleUriPathSegment = $initialUriPathSegment . '-' . $i++;
        }
        return $possibleUriPathSegment;
    }

    /**
     * @return string|null
     * @throws NodeException
     */
    protected function findWithFlowQuery()
    {
        if (!$this->node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return null;
        }
        $q = new FlowQuery([$this->node]);
        $q = $q->context([
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        $possibleUriPathSegment = $initialUriPathSegment = !$this->node->hasProperty('uriPathSegment') ? $this->node->getName() : $this->node->getProperty('uriPathSegment');
        $i = 1;
        while ($q->siblings('[instanceof Neos.Neos:Document][uriPathSegment="' . $possibleUriPathSegment . '"]')->count() > 0) {
            $possibleUriPathSegment = $initialUriPathSegment . '-' . $i++;
        }
        return $possibleUriPathSegment;
    }
}
