<?php
namespace Neos\Media\Domain\Service;

use Neos\Media\Domain\Model\Dto\AssetResourceReplaced;

/**
 * Contract for handling any other
 */
interface AssetResourceReplacementFollowUpInterface
{
    public function handle(AssetResourceReplaced $assetResourceReplaced): void;
}
