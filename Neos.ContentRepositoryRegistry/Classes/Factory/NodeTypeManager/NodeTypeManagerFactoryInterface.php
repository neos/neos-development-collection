<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

interface NodeTypeManagerFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $nodeTypeManagerPreset): NodeTypeManager;
}
