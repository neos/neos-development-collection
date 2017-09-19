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
use Neos\ContentRepository\Exception;

/**
 * A "node type not found" exception
 *
 */
class NodeTypeNotFoundException extends Exception
{

    /**
     * @var string
     */
    protected $nodeTypeName;

    public function __construct($message = "", $code = 0, string $nodeTypeName)
    {
        parent::__construct($message, $code);
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * @return string
     */
    public function getNodeTypeName(): string
    {
        return $this->nodeTypeName;
    }

}
