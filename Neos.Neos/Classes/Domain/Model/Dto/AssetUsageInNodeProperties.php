<?php
namespace Neos\Neos\Domain\Model\Dto;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;

/**
 * A DTO for storing information related to a usage of an asset in node properties.
 */
class AssetUsageInNodeProperties extends UsageReference
{
    /**
     * @var string
     */
    protected $nodeIdentifier;

    /**
     * @var string
     */
    protected $workspaceName;

    /**
     * @var array<string,string>
     */
    protected $dimensionValues;

    /**
     * @var string
     */
    protected $nodeTypeName;

    /**
     * @param AssetInterface $asset
     * @param string $nodeIdentifier
     * @param string $workspaceName
     * @param array<string,string> $dimensionValues
     * @param string $nodeTypeName
     */
    public function __construct(AssetInterface $asset, $nodeIdentifier, $workspaceName, $dimensionValues, $nodeTypeName)
    {
        parent::__construct($asset);
        $this->nodeIdentifier = $nodeIdentifier;
        $this->workspaceName = $workspaceName;
        $this->dimensionValues = $dimensionValues;
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * @return string
     */
    public function getNodeIdentifier()
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return string
     */
    public function getWorkspaceName()
    {
        return $this->workspaceName;
    }

    /**
     * @return array<string,string>
     */
    public function getDimensionValues()
    {
        return $this->dimensionValues;
    }

    /**
     * @return string
     */
    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }
}
