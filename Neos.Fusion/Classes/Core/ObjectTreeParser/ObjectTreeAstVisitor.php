<?php

namespace Neos\Fusion\Core\ObjectTreeParser;

use Neos\Fusion\Core\ObjectTreeParser\Ast\AssignedObjectPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\BlockAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\CharValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFileAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionObjectValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IncludeAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\MetaPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectDefinitionAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPathPartAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PrototypePathAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\SimpleValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementListAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StringValueAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignmentAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueCopyAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueUnsetAst;
use Neos\Fusion;

class ObjectTreeAstVisitor extends AstNodeVisitor
{
    /**
     * @var ObjectTree
     */
    protected $objectTree;

    /**
     * For nested blocks to determine the prefix
     * @var array
     */
    protected $currentObjectPathStack = [];

    /**
     * @var ?string
     */
    protected $contextPathAndFilename;

    /**
     * @var callable
     */
    protected $handleFileInclude;

    /**
     * @var callable
     */
    protected $handleDslTranspile;

    public function __construct(ObjectTree $objectTree, callable $handleFileInclude, callable $handleDslTranspile)
    {
        $this->objectTree = $objectTree;
        $this->handleFileInclude = $handleFileInclude;
        $this->handleDslTranspile = $handleDslTranspile;
    }

    public function visitFusionFileAst(FusionFileAst $fusionFileAst): ObjectTree
    {
        $this->contextPathAndFilename = $fusionFileAst->getContextPathAndFileName();
        $fusionFileAst->getStatementList()->visit($this);
        return $this->objectTree;
    }

    public function visitStatementListAst(StatementListAst $statementListAst)
    {
        foreach ($statementListAst->getStatements() as $statement) {
            $statement->visit($this);
        }
    }

    public function visitIncludeAst(IncludeAst $includeAst)
    {
        ($this->handleFileInclude)($this->objectTree, $includeAst->getFilePattern(), $this->contextPathAndFilename);
    }

    public function visitObjectDefinitionAst(ObjectDefinitionAst $objectDefinitionAst)
    {
        $currentPath = $objectDefinitionAst->getPath()->visit($this, $this->getCurrentObjectPathPrefix());

        $operation = $objectDefinitionAst->getOperation();

        if ($operation !== null) {
            $operation->visit($this, $currentPath);
        }

        $block = $objectDefinitionAst->getBlock();

        if ($block !== null) {
            $block->visit($this, $currentPath);
        }
    }

    public function visitBlockAst(BlockAst $blockAst, array $currentPath = null)
    {
        if ($currentPath === null) {
            throw new \Exception();
        }
        array_push($this->currentObjectPathStack, $currentPath);

        $blockAst->getStatementList()->visit($this);

        array_pop($this->currentObjectPathStack);
    }

    public function visitObjectPathAst(ObjectPathAst $objectPathAst, array $objectPathPrefix = []): array
    {
        $path = $objectPathPrefix;
        foreach ($objectPathAst->getSegments() as $segment) {
            array_push($path, ...$segment->visit($this));
        }
        return $path;
    }

    public function visitMetaPathAst(MetaPathAst $metaPathAst): array
    {
        return ['__meta', $metaPathAst->getIdentifier()];
    }

    public function visitPrototypePathAst(PrototypePathAst $prototypePathAst): array
    {
        return ['__prototypes', $prototypePathAst->getIdentifier()];
    }

    public function visitObjectPathPartAst(ObjectPathPartAst $objectPathPartAst): array
    {
        $key = stripslashes($objectPathPartAst->getIdentifier());
        self::validateParseTreeKey($key);
        return [$key];
    }

    public function visitValueAssignmentAst(ValueAssignmentAst $valueAssignmentAst, array $currentPath = null)
    {
        if ($currentPath === null) {
            throw new \Exception();
        }
        $value = $valueAssignmentAst->getPathValue()->visit($this);
        $this->objectTree->setValueInObjectTree($currentPath, $value);
    }

    public function visitFusionObjectValueAst(FusionObjectValueAst $fusionObjectValueAst)
    {
        return [
            '__objectType' => $fusionObjectValueAst->getValue(), '__value' => null, '__eelExpression' => null
        ];
    }

    public function visitDslExpressionValueAst(DslExpressionValueAst $dslExpressionValueAst)
    {
        return ($this->handleDslTranspile)($dslExpressionValueAst->getIdentifier(), $dslExpressionValueAst->getCode());
    }

    public function visitEelExpressionValueAst(EelExpressionValueAst $eelExpressionValueAst)
    {
        $eelWithoutNewLines = str_replace("\n", '', $eelExpressionValueAst->getValue());
        return [
            '__eelExpression' => $eelWithoutNewLines, '__value' => null, '__objectType' => null
        ];
    }

    public function visitSimpleValueAst(SimpleValueAst $simpleValueAst)
    {
        return $simpleValueAst->getValue();
    }

    public function visitCharValueAst(CharValueAst $charValueAst)
    {
        return stripslashes($charValueAst->getValue());
    }

    public function visitStringValueAst(StringValueAst $stringValueAst)
    {
        return stripcslashes($stringValueAst->getValue());
    }

    public function visitValueCopyAst(ValueCopyAst $valueCopyAst, array $currentPath = null)
    {
        if ($currentPath === null) {
            throw new \Exception();
        }
        $sourcePath = $valueCopyAst->getAssignedObjectPath()->visit($this, $this->objectTree->getParentPath($currentPath));

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

    public function visitAssignedObjectPathAst(AssignedObjectPathAst $assignedObjectPathAst, $relativePath = [])
    {
        $path = [];
        if ($assignedObjectPathAst->isRelative()) {
            $path = $relativePath;
        }
        return $assignedObjectPathAst->getObjectPath()->visit($this, $path);
    }

    public function visitValueUnsetAst(ValueUnsetAst $valueUnsetAst, array $currentPath = null)
    {
        if ($currentPath === null) {
            throw new \Exception();
        }
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
        if (substr($pathKey, 0, 2) === '__'
            && in_array($pathKey, Fusion\Core\ParserInterface::RESERVED_PARSE_TREE_KEYS, true)) {
            throw new Fusion\Exception("Reversed key '$pathKey' used.", 1437065270);
        }
        if (strpos($pathKey, "\n") !== false) {
            throw new Fusion\Exception("Key '$pathKey' cannot contain spaces.", 1644068086);
        }
    }
}
