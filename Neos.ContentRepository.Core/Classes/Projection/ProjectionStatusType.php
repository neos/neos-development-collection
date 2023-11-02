<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

enum ProjectionStatusType: string
{
    case OK = 'OK';
    case REQUIRES_SETUP = 'REQUIRES_SETUP';
    case REQUIRES_REPLAY = 'REQUIRES_REPLAY';
}
