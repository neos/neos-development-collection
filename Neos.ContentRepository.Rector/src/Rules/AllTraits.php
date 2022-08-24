<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules;

use Neos\ContentRepository\Rector\Rules\Traits\ContentRepositoryTrait;
use Neos\ContentRepository\Rector\Rules\Traits\FunctionsTrait;
use Neos\ContentRepository\Rector\Rules\Traits\NodeHiddenStateFinderTrait;
use Neos\ContentRepository\Rector\Rules\Traits\NodeTrait;
use Neos\ContentRepository\Rector\Rules\Traits\SubgraphTrait;
use Neos\ContentRepository\Rector\Rules\Traits\ThisTrait;
use Neos\ContentRepository\Rector\Rules\Traits\ValueObjectTrait;

trait AllTraits
{
    use FunctionsTrait;

    use ContentRepositoryTrait;
    use NodeHiddenStateFinderTrait;
    use NodeTrait;
    use SubgraphTrait;
    use ThisTrait;
    use ValueObjectTrait;
}
