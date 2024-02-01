<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

enum CheckpointStorageStatusType
{
    case OK;
    case ERROR;
    case SETUP_REQUIRED;
}
