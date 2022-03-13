<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

interface ContextInNodeBasedReadModelInterface
{
    /**
     * @return EmulatedLegacyContext
     */
    public function getContext();
}
