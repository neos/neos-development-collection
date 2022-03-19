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

interface AstNodeVisitor
{
    public function visitFusionFile(FusionFile $fusionFile);
    public function visitStatementList(StatementList $statementList);
    public function visitIncludeStatement(IncludeStatement $includeStatement);
    public function visitObjectStatement(ObjectStatement $objectStatement);
    public function visitBlock(Block $block);
    public function visitObjectPath(ObjectPath $objectPath);
    public function visitMetaPathSegment(MetaPathSegment $metaPathSegment);
    public function visitPrototypePathSegment(PrototypePathSegment $prototypePathSegment);
    public function visitPathSegment(PathSegment $pathSegment);
    public function visitValueAssignment(ValueAssignment $valueAssignment);
    public function visitFusionObjectValue(FusionObjectValue $fusionObjectValue);
    public function visitDslExpressionValue(DslExpressionValue $dslExpressionValue);
    public function visitEelExpressionValue(EelExpressionValue $eelExpressionValue);
    public function visitSimpleValue(SimpleValue $simpleValue);
    public function visitCharValue(CharValue $charValue);
    public function visitStringValue(StringValue $stringValue);
    public function visitValueCopy(ValueCopy $valueCopy);
    public function visitAssignedObjectPath(AssignedObjectPath $assignedObjectPath);
    public function visitValueUnset(ValueUnset $valueUnset);
}
