<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\ValueObject;

enum AssetType: string
{
    case IMAGE = 'IMAGE';
    case AUDIO = 'AUDIO';
    case DOCUMENT = 'DOCUMENT';
    case VIDEO = 'VIDEO';
}
