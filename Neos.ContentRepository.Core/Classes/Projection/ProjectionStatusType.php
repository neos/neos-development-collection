<?php

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
enum ProjectionStatusType
{
    case OK;
    case ERROR;
    case SETUP_REQUIRED;
    case REPLAY_REQUIRED;
}
