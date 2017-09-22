<?php
namespace Neos\ContentRepository\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Exception;

/**
 * An node not found exception
 *
 */
class NodeNotFoundException extends Exception
{

    /**
     * @var NodeIdentifier
     */
    protected $nodeIdentifier;

    /**
     * NodeNotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param NodeIdentifier $nodeIdentifier
     */
    public function __construct($message = "", $code = 0, NodeIdentifier $nodeIdentifier)
    {
        parent::__construct($message, $code);
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }
}
