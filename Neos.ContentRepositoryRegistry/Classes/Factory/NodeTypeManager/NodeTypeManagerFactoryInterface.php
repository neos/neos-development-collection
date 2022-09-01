<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

interface NodeTypeManagerFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $nodeTypeManagerPreset): NodeTypeManager;
}
