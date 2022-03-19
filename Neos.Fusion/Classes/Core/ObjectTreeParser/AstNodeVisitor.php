<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFile;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementList;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IncludeStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\Block;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\MetaPathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PrototypePathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionObjectValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\SimpleValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\CharValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StringValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueCopy;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AssignedObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueUnset;

abstract class AstNodeVisitor
{
    abstract public function visitFusionFile(FusionFile $fusionFile);
    abstract public function visitStatementList(StatementList $statementList);
    abstract public function visitIncludeStatement(IncludeStatement $includeStatement);
    abstract public function visitObjectStatement(ObjectStatement $objectStatement);
    abstract public function visitBlock(Block $block);
    abstract public function visitObjectPath(ObjectPath $objectPath);
    abstract public function visitMetaPathSegment(MetaPathSegment $metaPathSegment);
    abstract public function visitPrototypePathSegment(PrototypePathSegment $prototypePathSegment);
    abstract public function visitPathSegment(PathSegment $pathSegment);
    abstract public function visitValueAssignment(ValueAssignment $valueAssignment);
    abstract public function visitFusionObjectValue(FusionObjectValue $fusionObjectValue);
    abstract public function visitDslExpressionValue(DslExpressionValue $dslExpressionValue);
    abstract public function visitEelExpressionValue(EelExpressionValue $eelExpressionValue);
    abstract public function visitSimpleValue(SimpleValue $simpleValue);
    abstract public function visitCharValue(CharValue $charValue);
    abstract public function visitStringValue(StringValue $stringValue);
    abstract public function visitValueCopy(ValueCopy $valueCopy);
    abstract public function visitAssignedObjectPath(AssignedObjectPath $assignedObjectPath);
    abstract public function visitValueUnset(ValueUnset $valueUnset);
}
