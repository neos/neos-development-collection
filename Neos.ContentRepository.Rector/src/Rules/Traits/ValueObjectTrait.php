<?php
declare (strict_types=1);

namespace Neos\ContentRepository\Rector\Rules\Traits;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;

trait ValueObjectTrait
{

    /**
     * @var \Rector\Core\PhpParser\Node\NodeFactory
     */
    protected $nodeFactory;

    private function contentRepositoryIdentifier_fromString(string $contentRepositoryName)
    {
        return $this->nodeFactory->createStaticCall(ContentRepositoryIdentifier::class, 'fromString', [new String_($contentRepositoryName)]);
    }

    private function workspaceName_fromString(Expr $expr): Expr
    {
        return $this->nodeFactory->createStaticCall(WorkspaceName::class, 'fromString', [$expr]);
    }

    private function nodeTypeName_fromString(string $param)
    {
        return $this->nodeFactory->createStaticCall(NodeTypeName::class, 'fromString', [new String_($param)]);
    }


    private function dimensionSpacePoint_fromLegacyDimensionArray(Expr $legacyDimensionArray)
    {
        return $this->nodeFactory->createStaticCall(DimensionSpacePoint::class, 'fromLegacyDimensionArray', [$legacyDimensionArray]);
    }
}
