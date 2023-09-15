<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Fusion\Core\FusionGlobals;

final readonly class FusionRenderingStuff
{
    public function __construct(
        public Site $site,
        public RenderingContext $fusionContext,
        public FusionGlobals $fusionGlobals
    ) {
    }

    public function withMergedFusionGlobals(FusionGlobals $otherFusionGlobals): self
    {
        return new self(
            $this->site,
            $this->fusionContext,
            $this->fusionGlobals->merge($otherFusionGlobals)
        );
    }
}
