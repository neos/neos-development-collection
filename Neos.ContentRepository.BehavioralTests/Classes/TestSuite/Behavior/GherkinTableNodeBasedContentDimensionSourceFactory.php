<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GherkinTableNodeBasedContentDimensionSource;
use Neos\ContentRepositoryRegistry\Factory\ContentDimensionSource\ContentDimensionSourceFactoryInterface;

class GherkinTableNodeBasedContentDimensionSourceFactory implements ContentDimensionSourceFactoryInterface
{
    public static ?ContentDimensionSourceInterface $contentDimensionsToUse = null;

    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentDimensionSourceInterface
    {
        return self::$contentDimensionsToUse;
    }

    public static function initializeFromTableNode(TableNode $contentDimensionsToUse): void
    {
        self::$contentDimensionsToUse = GherkinTableNodeBasedContentDimensionSource::fromGherkinTableNode($contentDimensionsToUse);
    }

    public static function reset(): void
    {
        self::$contentDimensionsToUse = null;
    }
}
