<?php
namespace Neos\Media\Domain\Model;

use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;

interface AdjustmentCapableInterface
{
    /** @return iterable<ImageAdjustmentInterface> */
    public function getAdjustments(): iterable;

    public function addAdjustment(ImageAdjustmentInterface $adjustment): void;

    /**
     * @param ImageAdjustmentInterface[] $adjustments
     * @return void
     */
    public function addAdjustments(array $adjustments): void;
}
