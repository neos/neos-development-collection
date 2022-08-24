<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Rector\Legacy;

use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;

/**
 * This is a class needed during code migration; to get rid of Context and ContextFactory:
 *
 * all ContextFactory::create calls are converted to new LegacyContextStub($properties); so that the
 * LegacyContextStub can then be passed to other parts of the codebase. Then,
 * we can rewrite the Context calls to the ContentGraph / ContentSubgraph API.
 *
 * PEOPLE SHOULD REMOVE USAGES OF THIS CLASS AS QUICKLY AS POSSIBLE; IT IS JUST TO HELP DURING MIGRATION
 *
 * @deprecated !!! rewrite your code to not use this class.
 */
class LegacyContextStub {
    /**
     * @deprecated
     */
    public readonly string|null $workspaceName;
    /**
     * @deprecated
     */
    public readonly array|null $dimensions;
    /**
     * @deprecated
     */
    public readonly array|null $targetDimensions;
    /**
     * @deprecated
     */
    public readonly bool $invisibleContentShown;
    /**
     * @deprecated
     */
    public readonly bool $removedContentShown;
    /**
     * @deprecated
     */
    public readonly bool $inaccessibleContentShown;
    /**
     * @deprecated
     */
    public readonly Site|null $currentSite;
    /**
     * @deprecated
     */
    public readonly Domain|null $currentDomain;

    public function __construct(array $contextConfiguration)
    {
        $this->workspaceName = $contextConfiguration['workspaceName'] ?? null;
        $this->dimensions = $contextConfiguration['dimensions'] ?? [];
        $this->targetDimensions = $contextConfiguration['targetDimensions'] ?? [];
        $this->invisibleContentShown = $contextConfiguration['invisibleContentShown'] ?? false;
        $this->removedContentShown = $contextConfiguration['removedContentShown'] ?? false;
        $this->inaccessibleContentShown = $contextConfiguration['inaccessibleContentShown'] ?? false;
        $this->currentSite = $contextConfiguration['currentSite'] ?? null;
        $this->currentDomain = $contextConfiguration['currentDomain'] ?? null;
    }
}
