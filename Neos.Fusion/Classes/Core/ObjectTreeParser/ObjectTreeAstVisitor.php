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
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;

class ObjectTreeAstVisitor extends AstNodeVisitor
{
    /**
     * For nested blocks to determine the prefix
     */
    protected array $currentObjectPathStack = [];

    protected ?string $contextPathAndFilename;

    protected int $currentObjectStatementCursor;

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
        $this->currentObjectStatementCursor = $objectStatement->cursor;

        $currentPath = $objectStatement->path->visit($this, $this->getCurrentObjectPathPrefix());

        $objectStatement->operation?->visit($this, $currentPath);
        $objectStatement->block?->visit($this, $currentPath);
    }

    public function visitBlock(Block $block, array $currentPath = null)
    {
        $currentPath ?? throw new \BadMethodCallException('$currentPath is required.');

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
        $currentPath ?? throw new \BadMethodCallException('$currentPath is required.');

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
        try {
            return ($this->handleDslTranspile)($dslExpressionValue->identifier, $dslExpressionValue->code);
        } catch (ParserException $e) {
            throw $e;
        } catch (\Exception $e) {
            // convert all exceptions from dsl transpilation to fusion exception and add file and line info
            throw $this->prepareParserException(new ParserException())
                ->withCode(1180600696)
                ->withMessage($e->getMessage())
                ->build();
        }
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
        $currentPath ?? throw new \BadMethodCallException('$currentPath is required.');

        $sourcePath = $valueCopy->assignedObjectPath->visit($this, $this->objectTree->getParentPath($currentPath));

        $currentPathsPrototype = $this->objectTree->objectPathIsPrototype($currentPath);
        $sourcePathIsPrototype = $this->objectTree->objectPathIsPrototype($sourcePath);
        if ($currentPathsPrototype && $sourcePathIsPrototype) {
            // both are a prototype definition
            if (count($currentPath) !== 2 || count($sourcePath) !== 2) {
                // one of the path has not a length of 2: this means
                // at least one path is nested (f.e. foo.prototype(Bar))
                // Currently, it is not supported to override the prototypical inheritance in
                // parts of the Fusion rendering tree.
                // Although this might work conceptually, it makes reasoning about the prototypical
                // inheritance tree a lot more complex; that's why we forbid it right away.
                throw $this->prepareParserException(new ParserException())
                    ->withCode(1358418019)
                    ->withMessage('Cannot inherit, when one of the sides is nested (e.g. foo.prototype(Bar)). Setting up prototype inheritance is only supported at the top level: prototype(Foo) < prototype(Bar)')
                    ->build();
            }
            // it must be of the form "prototype(Foo) < prototype(Bar)"
            $currentPath[] = '__prototypeObjectName';
            $this->objectTree->setValueInObjectTree($currentPath, end($sourcePath));
            return;
        }

        if ($currentPathsPrototype xor $sourcePathIsPrototype) {
            // Only one of "source" or "target" is a prototype. We do not support copying a
            // non-prototype value to a prototype value or vice-versa.
            throw $this->prepareParserException(new ParserException())
                ->withCode(1358418015)
                ->withMessage("Cannot inherit, when one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.")
                ->build();
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
        $currentPath ?? throw new \BadMethodCallException('$currentPath is required.');

        $this->objectTree->removeValueInObjectTree($currentPath);
    }

    protected function getCurrentObjectPathPrefix(): array
    {
        $lastElementOfStack = end($this->currentObjectPathStack);
        return ($lastElementOfStack === false) ? [] : $lastElementOfStack;
    }

    protected function validateParseTreeKey(string $pathKey)
    {
        if ($pathKey === '') {
            throw $this->prepareParserException(new ParserException())
                ->withCode(1646988838)
                ->withMessage("A path must not be empty.")
                ->build();
        }
        if (str_starts_with($pathKey, '__')
            && in_array($pathKey, Fusion\Core\ParserInterface::RESERVED_PARSE_TREE_KEYS, true)) {
            throw $this->prepareParserException(new ParserException())
                ->withCode(1437065270)
                ->withMessage("Reversed key '$pathKey' used.")
                ->build();
        }
        if (str_contains($pathKey, "\n")) {
            $cleaned = str_replace("\n", '', $pathKey);
            throw $this->prepareParserException(new ParserException())
                ->withCode(1644068086)
                ->withMessage("Key '$cleaned' cannot contain newlines.")
                ->build();
        }
    }

    protected function prepareParserException(ParserException $parserException): ParserException
    {
        if ($this->contextPathAndFilename === null) {
            $fusionCode = '';
        } else {
            $fusionCode = file_get_contents($this->contextPathAndFilename);
        }
        return $parserException
            ->withoutColumnShown()
            ->withFile($this->contextPathAndFilename)
            ->withFusion($fusionCode)
            ->withCursor($this->currentObjectStatementCursor);
    }
}
