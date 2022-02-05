<?php
namespace Neos\Fusion\Core\ObjectTreeParser;

use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFileAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementListAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IncludeAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectDefinitionAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\BlockAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PathSegmentAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\MetaPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PrototypePathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPathPartAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignmentAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PathValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionObjectValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\SimpleValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\CharValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StringValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueCopyAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AssignedObjectPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueUnsetAst;

abstract class AstNodeVisitor
{
    abstract public function visitFusionFileAst(FusionFileAst $fusionFileAst);
    abstract public function visitStatementListAst(StatementListAst $statementListAst);
    abstract public function visitStatementAst(StatementAst $statementAst);
    abstract public function visitIncludeAst(IncludeAst $includeAst);
    abstract public function visitObjectDefinitionAst(ObjectDefinitionAst $objectDefinitionAst);
    abstract public function visitBlockAst(BlockAst $blockAst);
    abstract public function visitObjectPathAst(ObjectPathAst $objectPathAst);
    abstract public function visitPathSegmentAst(PathSegmentAst $pathSegmentAst);
    abstract public function visitMetaPathAst(MetaPathAst $metaPathAst);
    abstract public function visitPrototypePathAst(PrototypePathAst $prototypePathAst);
    abstract public function visitObjectPathPartAst(ObjectPathPartAst $objectPathPartAst);
    abstract public function visitValueAssignmentAst(ValueAssignmentAst $valueAssignmentAst);
    abstract public function visitPathValueAst(PathValueAst $pathValueAst);
    abstract public function visitFusionObjectValueAst(FusionObjectValueAst $fusionObjectValueAst);
    abstract public function visitDslExpressionValueAst(DslExpressionValueAst $dslExpressionValueAst);
    abstract public function visitEelExpressionValueAst(EelExpressionValueAst $eelExpressionValueAst);
    abstract public function visitSimpleValueAst(SimpleValueAst $simpleValueAst);
    abstract public function visitCharValueAst(CharValueAst $charValueAst);
    abstract public function visitStringValueAst(StringValueAst $stringValueAst);
    abstract public function visitValueCopyAst(ValueCopyAst $valueCopyAst);
    abstract public function visitAssignedObjectPathAst(AssignedObjectPathAst $assignedObjectPathAst);
    abstract public function visitValueUnsetAst(ValueUnsetAst $valueUnsetAst);
}
