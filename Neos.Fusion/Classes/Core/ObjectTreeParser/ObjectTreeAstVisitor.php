<?php

namespace Neos\Fusion\Core\ObjectTreeParser;

use Neos\Fusion\Core\ObjectTreeParser\Ast\AssignedObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\Block;
use Neos\Fusion\Core\ObjectTreeParser\Ast\CharValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFile;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionObjectValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IncludeStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\MetaPathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PrototypePathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\SimpleValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementList;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StringValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueCopy;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueUnset;
use Neos\Fusion;

class ObjectTreeAstVisitor extends AstNodeVisitor
{
    /**
     * For nested blocks to determine the prefix
     */
    protected array $currentObjectPathStack = [];

    protected ?string $contextPathAndFilename;

    public function __construct(
        protected ObjectTree $objectTree,
        protected \Closure $handleFileInclude,
        protected \Closure $handleDslTranspile
    ) {
    }

    public function visitFusionFile(FusionFile $fusionFile): ObjectTree
    {
        $this->contextPathAndFilename = $fusionFile->contextPathAndFileName;
        $fusionFile->statementList->visit($this);
        return $this->objectTree;
    }

    public function visitStatementList(StatementList $statementList)
    {
        foreach ($statementList->statements as $statement) {
            $statement->visit($this);
        }
    }

    public function visitIncludeStatement(IncludeStatement $includeStatement)
    {
        ($this->handleFileInclude)($this->objectTree, $includeStatement->filePattern, $this->contextPathAndFilename);
    }

    public function visitObjectStatement(ObjectStatement $objectStatement)
    {
        $currentPath = $objectStatement->path->visit($this, $this->getCurrentObjectPathPrefix());

        $operation = $objectStatement->operation;

        $operation?->visit($this, $currentPath);

        $block = $objectStatement->block;

        $block?->visit($this, $currentPath);
    }

    public function visitBlock(Block $block, array $currentPath = null)
    {
        $currentPath ?? throw new \Exception();

        array_push($this->currentObjectPathStack, $currentPath);

        $block->statementList->visit($this);

        array_pop($this->currentObjectPathStack);
    }

    public function visitObjectPath(ObjectPath $objectPath, array $objectPathPrefix = []): array
    {
        $path = $objectPathPrefix;
        foreach ($objectPath->segments as $segment) {
            $path = [...$path, ...$segment->visit($this)];
        }
        return $path;
    }

    public function visitMetaPathSegment(MetaPathSegment $metaPathSegment): array
    {
        return ['__meta', $metaPathSegment->identifier];
    }

    public function visitPrototypePathSegment(PrototypePathSegment $prototypePathSegment): array
    {
        return ['__prototypes', $prototypePathSegment->identifier];
    }

    public function visitPathSegment(PathSegment $pathSegment): array
    {
        $key = stripslashes($pathSegment->identifier);
        self::validateParseTreeKey($key);
        return [$key];
    }

    public function visitValueAssignment(ValueAssignment $valueAssignment, array $currentPath = null)
    {
        $currentPath ?? throw new \Exception();

        $value = $valueAssignment->pathValue->visit($this);
        $this->objectTree->setValueInObjectTree($currentPath, $value);
    }

    public function visitFusionObjectValue(FusionObjectValue $fusionObjectValue)
    {
        return [
            '__objectType' => $fusionObjectValue->value, '__value' => null, '__eelExpression' => null
        ];
    }

    public function visitDslExpressionValue(DslExpressionValue $dslExpressionValue)
    {
        return ($this->handleDslTranspile)($dslExpressionValue->identifier, $dslExpressionValue->code);
    }

    public function visitEelExpressionValue(EelExpressionValue $eelExpressionValue)
    {
        $eelWithoutNewLines = str_replace("\n", '', $eelExpressionValue->value);
        return [
            '__eelExpression' => $eelWithoutNewLines, '__value' => null, '__objectType' => null
        ];
    }

    public function visitSimpleValue(SimpleValue $simpleValue)
    {
        return $simpleValue->value;
    }

    public function visitCharValue(CharValue $charValue): string
    {
        return stripslashes($charValue->value);
    }

    public function visitStringValue(StringValue $stringValue): string
    {
        return stripcslashes($stringValue->value);
    }

    public function visitValueCopy(ValueCopy $valueCopy, array $currentPath = null)
    {
        $currentPath ?? throw new \Exception();

        $sourcePath = $valueCopy->assignedObjectPath->visit($this, $this->objectTree->getParentPath($currentPath));

        $currentPathsPrototype = $this->objectTree->objectPathIsPrototype($currentPath);
        $sourcePathIsPrototype = $this->objectTree->objectPathIsPrototype($sourcePath);
        if ($currentPathsPrototype && $sourcePathIsPrototype) {
            // both are a prototype definition
            try {
                $this->objectTree->inheritPrototypeInObjectTree($currentPath, $sourcePath);
            } catch (Fusion\Exception $e) {
                // TODO show also line snipped, but for that to work we need to know the cursor position.
                throw $e;
            }
            return;
        }

        if ($currentPathsPrototype xor $sourcePathIsPrototype) {
            // Only one of "source" or "target" is a prototype. We do not support copying a
            // non-prototype value to a prototype value or vice-versa.
            // delay throw since there might be syntax errors causing this.

            // TODO show also line snipped, but for that to work we need to know the cursor position.
            throw new Fusion\Exception("Cannot inherit, when one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.", 1358418015);
        }

        $this->objectTree->copyValueInObjectTree($currentPath, $sourcePath);
    }

    public function visitAssignedObjectPath(AssignedObjectPath $assignedObjectPath, $relativePath = [])
    {
        $path = [];
        if ($assignedObjectPath->isRelative) {
            $path = $relativePath;
        }
        return $assignedObjectPath->objectPath->visit($this, $path);
    }

    public function visitValueUnset(ValueUnset $valueUnset, array $currentPath = null)
    {
        $currentPath ?? throw new \Exception();

        $this->objectTree->removeValueInObjectTree($currentPath);
    }

    protected function getCurrentObjectPathPrefix(): array
    {
        $lastElementOfStack = end($this->currentObjectPathStack);
        return ($lastElementOfStack === false) ? [] : $lastElementOfStack;
    }

    protected static function validateParseTreeKey(string $pathKey)
    {
        // TODO show also line snipped (in exceptions), but for that to work we need to know the cursor position.
        if (str_starts_with($pathKey, '__')
            && in_array($pathKey, Fusion\Core\ParserInterface::RESERVED_PARSE_TREE_KEYS, true)) {
            throw new Fusion\Exception("Reversed key '$pathKey' used.", 1437065270);
        }
        if (str_contains($pathKey, "\n")) {
            throw new Fusion\Exception("Key '$pathKey' cannot contain spaces.", 1644068086);
        }
    }
}
