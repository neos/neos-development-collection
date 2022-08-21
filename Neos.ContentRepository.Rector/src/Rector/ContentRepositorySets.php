<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Rector;

use Rector\Set\Contract\SetListInterface;

class ContentRepositorySets implements SetListInterface
{
    public const CONTENTREPOSITORY_9_0 = __DIR__ . '/../../config/set/contentrepository-90.php';
}
