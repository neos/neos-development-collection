<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
enum ProjectionStatusType
{
    /**
     * No actions needed
     */
    case OK;
    /**
     * The projection needs to be setup to adjust its schema
     * {@see \Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer::setUp()}
     */
    case SETUP_REQUIRED;
    /**
     * An error occurred while determining the status (e.g. connection is closed)
     */
    case ERROR;
}
