<?php
declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph backend route suffix
 */
final class WorkspaceNameAndDimensionSpacePointForUriSerialization
{
    const FROM_BACKEND_URI_PATTERN = '/
        ^                       # we start at the first character
        .*
        @                       # an "@" character
        (?P<WorkspaceName>       # the workspace name as capture group
            [\w-]+
        )
        (?:                     # the dimension part is optional
            ;                   # semi-colon
            (?P<DimensionComponents>
                .*
            )                   # everything until the rest of the string
        )?
        $                       # we consume the full string
    /x';

    /**
     * @var WorkspaceName
     */
    protected $workspaceName;

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * WorkspaceNameAndDimensionSpacePointForUriSerialization constructor.
     * @param WorkspaceName $workspaceName
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @Flow\Autowiring(false)
     */
    protected function __construct(WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint)
    {
        $this->workspaceName = $workspaceName;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
    }

    /**
     * Determine if the given node path is a context path.
     *
     * @param string $contextPath
     * @return boolean
     */
    public static function isParseablebackendUri($contextPath)
    {
        return (strpos($contextPath, '@') !== false);
    }

    public static function fromWorkspaceAndDimensionSpacePoint(
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint
    ): self {
        return new self($workspaceName, $dimensionSpacePoint);
    }

    public static function fromBackendUri(string $backendUri): WorkspaceNameAndDimensionSpacePointForUriSerialization
    {
        $matches = [];
        if (!preg_match(self::FROM_BACKEND_URI_PATTERN, $backendUri, $matches)) {
            throw new \RuntimeException('TODO: Backend URI ' . $backendUri . ' could not be parsed.', 1519746339);
        }

        $workspaceName = WorkspaceName::fromString($matches['WorkspaceName']);
        $coordinates = [];
        if (isset($matches['DimensionComponents'])) {
            parse_str($matches['DimensionComponents'], $coordinates);
        }

        $dimensionSpacePoint = DimensionSpacePoint::fromArray($coordinates);

        return new WorkspaceNameAndDimensionSpacePointForUriSerialization($workspaceName, $dimensionSpacePoint);
    }

    /**
     * convert the WorkspaceName and DimensionSpacePoint to a string which is the postfix of the backend URI (after
     * the uriPath of the nodes)
     * @return string
     */
    public function toBackendUriSuffix(): string
    {
        $dimensionComponents = [];
        foreach ($this->dimensionSpacePoint->coordinates as $dimensionName => $dimensionValue) {
            $dimensionComponents[] = $dimensionName . '=' . $dimensionValue;
        }
        $dimensionSuffix = '';
        if (!empty($dimensionComponents)) {
            $dimensionSuffix = ';' . implode('&', $dimensionComponents);
        }
        return '@' . $this->workspaceName . $dimensionSuffix;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toBackendUriSuffix();
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }
}
