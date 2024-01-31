<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Domain;

enum HandlingResultType: string
{
    case ENABLED = 'ENABLED';
    case DISABLED = 'DISABLED';
}
