<?php
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph backend route suffix
 */
final class ContentSubgraphBackendRouteSuffix
{
    /**
     * @var string
     */
    protected $suffix;


    /**
     * @param string $suffix
     */
    public function __construct(string $suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * @param WorkspaceName $workspaceName
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphBackendRouteSuffix
     */
    public static function fromWorkspaceAndDimensionSpacePoint(WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint)
    {
        $dimensionComponents = [];
        foreach ($dimensionSpacePoint->getCoordinates() as $dimensionName => $dimensionValue) {
            $dimensionComponents[] = $dimensionName . '=' . $dimensionValue;
        }
        $dimensionSuffix = '';
        if (!empty($dimensionComponents)) {
            $dimensionSuffix = ';' . implode('&', $dimensionComponents);
        }
        return new ContentSubgraphBackendRouteSuffix('@' . $workspaceName . ';' . $dimensionSuffix);
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->suffix;
    }
}
