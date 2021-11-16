<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion;
use Neos\Fusion\Exception\ParserException;

/**
 * The Fusion Parser
 *
 * @api
 */
class Parser extends AbstractParser implements ParserInterface
{
    /**
     * Reserved parse tree keys for internal usage.
     *
     * @var array
     */
    public static $reservedParseTreeKeys = ['__meta', '__prototypes', '__stopInheritanceChain', '__prototypeObjectName', '__prototypeChain', '__value', '__objectType', '__eelExpression'];

    /**
     * @Flow\Inject
     * @var DslFactory
     */
    protected $dslFactory;

    /**
     * The Fusion object tree builder, used by this parser.
     * @var AstBuilder
     */
    protected $astBuilder;

    /**
     * For nested blocks to determine the prefix
     * @var array
     */
    protected $currentObjectPathStack = [];

    /**
     * @var string|null
     */
    protected $contextPathAndFilename;

    /**
     * @var ParserException|null
     */
    protected $delayedCombinedException;

    /**
     * Parses the given Fusion source code and returns an object tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string|null $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further Fusion files
     * @param array|AstBuilder|null $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @param boolean $buildPrototypeHierarchy Merge prototype configurations or not. Will be false for includes to only do that once at the end.
     * @return array A Fusion object tree, generated from the source code
     * @throws Fusion\Exception
     * @throws ParserException
     * @api
     */
    public function parse(string $sourceCode, string $contextPathAndFilename = null, $objectTreeUntilNow = null, bool $buildPrototypeHierarchy = true): array
    {
        if ($objectTreeUntilNow instanceof AstBuilder) {
            $this->astBuilder = $objectTreeUntilNow;
        } elseif ($objectTreeUntilNow === null) {
            $this->astBuilder = new AstBuilder();
        } elseif (\is_array($objectTreeUntilNow)) {
            $this->astBuilder = new AstBuilder();
            $this->astBuilder->setObjectTree($objectTreeUntilNow);
        } else {
            throw new Fusion\Exception('Cannot parse Fusion - $objectTreeUntilNow must be of type array or AstBuilder or null');
        }

        // TODO use dependency Injection, but this test doesnt like it Neos.Fusion/Tests/Unit/Core/ParserTest.php
        $this->lexer = new Lexer();
        $this->lexer->initialize($sourceCode);

        $this->contextPathAndFilename = $contextPathAndFilename;

        $this->parseFusion();

        if ($this->delayedCombinedException !== null) {
            throw $this->delayedCombinedException;
        }
        if ($buildPrototypeHierarchy) {
            $this->astBuilder->buildPrototypeHierarchy();
        }

        return $this->astBuilder->getObjectTree();
    }

    /**
     * Fusion
     *  = StatementList
     */
    protected function parseFusion(): void
    {
        try {
            $this->parseStatementList();
        } catch (ParserException $e) {
            throw $e;
        } catch (Fusion\Exception $e) {
            throw (new ParserException())
                ->withCode($e->getCode())
                ->withMessage('Exception while parsing: ' . $e->getMessage())
                ->withoutColumnShown()
                ->withPrevious($e)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->build();
        }
    }

    /**
     * StatementList
     *  = ( Statement )*
     *
     * @param int|null $stopLookahead When this tokenType is encountered the loop will be stopped
     */
    protected function parseStatementList(int $stopLookahead = null): void
    {
        $this->lazyBigGap();
        while ($this->accept(Token::EOF) === false
            && ($stopLookahead === null || $this->accept($stopLookahead) === false)) {
            $this->parseStatement();
            $this->lazyBigGap();
        }
    }

    /**
     * Statement
     *  = IncludeStatement / ObjectDefinition
     */
    protected function parseStatement(): void
    {
        // watch out for the order, its regex matching and first one wins.
        switch (true) {
            case $this->accept(Token::INCLUDE):
                $this->parseIncludeStatement();
                return;

            case $this->accept(Token::PROTOTYPE_START):
            case $this->accept(Token::OBJECT_PATH_PART):
            case $this->accept(Token::META_PATH_START):
            case $this->accept(Token::CHAR):
            case $this->accept(Token::STRING):
                $this->parseObjectDefinition();
                return;
        }

        throw (new ParserException())
            ->withCode(1635708717)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withParseStatement()
            ->build();
    }

    /**
     * ValueAssignment
     *  = ASSIGNMENT PathValue
     */
    protected function parseValueAssignment($currentPath): void
    {
        $this->expect(Token::ASSIGNMENT);
        $this->lazyExpect(Token::SPACE);
        $value = $this->parsePathValue();
        $this->astBuilder->setValueInObjectTree($currentPath, $value);
    }

    /**
     * ValueUnset
     *  = UNSET
     */
    protected function parseValueUnset($currentPath): void
    {
        $this->expect(Token::UNSET);
        $this->astBuilder->removeValueInObjectTree($currentPath);
    }

    /**
     * ValueCopy
     *  = COPY ObjectPathAssignment
     */
    protected function parseValueCopy($currentPath): void
    {
        $this->expect(Token::COPY);
        $this->lazyExpect(Token::SPACE);

        $sourcePath = $this->parseAssignedObjectPath(AstBuilder::getParentPath($currentPath));

        $currentPathsPrototype = AstBuilder::objectPathIsPrototype($currentPath);
        $sourcePathIsPrototype = AstBuilder::objectPathIsPrototype($sourcePath);
        if ($currentPathsPrototype && $sourcePathIsPrototype) {
            // both are a prototype definition
            try {
                $this->astBuilder->inheritPrototypeInObjectTree($currentPath, $sourcePath);
            } catch (Fusion\Exception $e) {
                // delay throw since there might be syntax errors causing this.
                $this->delayedCombinedException = (new ParserException())
                    ->withCode($e->getCode())
                    ->withMessage($e->getMessage())
                    ->withoutColumnShown()
                    ->withPrevious($this->delayedCombinedException)
                    ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                    ->build();
            }
            return;
        }

        if ($currentPathsPrototype || $sourcePathIsPrototype) {
            // Only one of "source" or "target" is a prototype. We do not support copying a
            // non-prototype value to a prototype value or vice-versa.
            // delay throw since there might be syntax errors causing this.
            $this->delayedCombinedException = (new ParserException())
                ->withCode(1358418015)
                ->withMessage("Cannot inherit, when one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.")
                ->withoutColumnShown()
                ->withPrevious($this->delayedCombinedException)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->build();
            return;
        }

        $this->astBuilder->copyValueInObjectTree($currentPath, $sourcePath);
    }

    /**
     * ObjectDefinition
     *  = ObjectPath ( ValueAssignment / ValueUnset / ValueCopy )? ( BlockStatement / EndOfStatement )
     */
    protected function parseObjectDefinition(): void
    {
        $currentPath = $this->parseObjectPath($this->getCurrentObjectPathPrefix());
        $this->lazyExpect(Token::SPACE);
        $cursorAfterObjectPath = $this->lexer->getCursor();

        $operationWasParsed = null;
        switch (true) {
            case $this->accept(Token::ASSIGNMENT):
                $this->parseValueAssignment($currentPath);
                break;

            case $this->accept(Token::UNSET):
                $this->parseValueUnset($currentPath);
                break;

            case $this->accept(Token::COPY):
                $this->parseValueCopy($currentPath);
                break;

            default:
                $operationWasParsed = false;
        }
        $this->lazyExpect(Token::SPACE);

        if ($this->accept(Token::LBRACE)) {
            $this->parseBlockStatement($currentPath);
            return;
        }

        if ($operationWasParsed === false) {
            throw (new ParserException())
                ->withCode(1635708717)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())
                ->withCursor($cursorAfterObjectPath)
                ->withParsePathOrOperator()
                ->build();
        }

        $this->parseEndOfStatement();
    }

    /**
     * IncludeStatement
     *  = INCLUDE ( StringLiteral / FILE_PATTERN ) EndOfStatement
     */
    protected function parseIncludeStatement(): void
    {
        $this->expect(Token::INCLUDE);
        $this->lazyExpect(Token::SPACE);

        switch (true) {
            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $filePattern = $this->parseStringLiteral();
                break;
            case $this->accept(Token::FILE_PATTERN):
                $filePattern = $this->consume()->getValue();
                break;
            default:
                throw (new ParserException())
                    ->withCode(1635708717)
                    ->withMessage('Expected file pattern in quotes or [a-zA-Z0-9.*:/_-]')
                    ->withUnexpectedChar()
                    ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                    ->build();
        }

        try {
            $this->includeAndParseFilesByPattern($filePattern);
        } catch (ParserException $e) {
            throw $e;
        } catch (Fusion\Exception $e) {
            throw (new ParserException())
                ->withCode($e->getCode())
                ->withMessage($e->getMessage())
                ->withoutColumnShown()
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->build();
        }

        $this->parseEndOfStatement();
    }

    /**
     * Parse an include files by pattern. Currently, we start a new parser object; but we could as well re-use
     * the given one.
     *
     * @param string $filePattern The include-pattern, for example " FooBar" or " resource://....". Can also include wildcard mask for Fusion globbing.
     * @throws Fusion\Exception
     */
    protected function includeAndParseFilesByPattern(string $filePattern): void
    {
        $parser = new Parser();

        $filesToInclude = FilePatternResolver::resolveFilesByPattern($filePattern, $this->contextPathAndFilename, '.fusion');
        foreach ($filesToInclude as $file) {
            if (is_readable($file) === false) {
                throw new Fusion\Exception("Could not read file '$file' of pattern '$filePattern'.", 1347977017);
            }
            // Check if not trying to recursively include the current file via globbing
            if (stat($this->contextPathAndFilename) !== stat($file)) {
                $parser->parse(file_get_contents($file), $file, $this->astBuilder, false);
            }
        }
    }

    /**
     * EndOfStatement
     *  = ( EOF / NEWLINE )
     */
    protected function parseEndOfStatement(): void
    {
        $this->lazySmallGap();

        if ($this->accept(Token::EOF)) {
            return;
        }
        if ($this->accept(Token::NEWLINE)) {
            $this->consume();
            return;
        }
        throw (new ParserException())
            ->withCode(1635878683)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withParseEndOfStatement()
            ->build();
    }

    /**
     * BlockStatement:
     *  = { StatementList? }
     */
    protected function parseBlockStatement(array $path): void
    {
        $this->expect(Token::LBRACE);
        $cursorPositionStartOfBlock = $this->lexer->getCursor() - 1;
        array_push($this->currentObjectPathStack, $path);

        $this->parseStatementList(Token::RBRACE);

        try {
            $this->expect(Token::RBRACE);
        } catch (Fusion\Exception $e) {
            throw (new ParserException())
                ->withCode(1635708717)
                ->withMessage('No closing brace "}" matched this starting block. Encountered <EOF>.')
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())
                ->withCursor($cursorPositionStartOfBlock)
                ->build();
        }
        array_pop($this->currentObjectPathStack);
    }

    /**
     * AbsoluteObjectPath
     *  = ( . )? ObjectPath
     *
     * @param array $relativePath If a dot is encountered a relative Path will be created. This determines the relation.
     */
    protected function parseAssignedObjectPath(array $relativePath): array
    {
        $objectPathPrefix = [];
        if ($this->lazyExpect(Token::DOT)) {
            $objectPathPrefix = $relativePath;
        }
        return $this->parseObjectPath($objectPathPrefix);
    }

    /**
     * ObjectPath
     *  = PathSegment ( . PathSegment )*
     *
     * @param array $objectPathPrefix The current base objectpath.
     */
    protected function parseObjectPath(array $objectPathPrefix = []): array
    {
        $objectPath = $objectPathPrefix;
        do {
            array_push($objectPath, ...$this->parsePathSegment());
        } while ($this->lazyExpect(Token::DOT));
        return $objectPath;
    }

    /**
     * PathSegment
     *  = ( PROTOTYPE_START FUSION_OBJECT_NAME ) / OBJECT_PATH_PART / @ OBJECT_PATH_PART / StringLiteral )
     */
    protected function parsePathSegment(): array
    {
        switch (true) {
            case $this->accept(Token::PROTOTYPE_START):
                $this->consume();
                $prototypeName = $this->expect(Token::FUSION_OBJECT_NAME)->getValue();
                $this->expect(Token::RPAREN);
                return ['__prototypes', $prototypeName];

            case $this->accept(Token::OBJECT_PATH_PART):
                $pathKey = $this->consume()->getValue();
                self::throwIfKeyIsReservedParseTreeKey($pathKey);
                return [$pathKey];

            case $this->accept(Token::META_PATH_START):
                $this->consume();
                $metaPathKey = $this->expect(Token::OBJECT_PATH_PART)->getValue();
                return ['__meta', $metaPathKey];

            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $quotedPathKey = $this->parseStringLiteral();
                if ($quotedPathKey === '') {
                    throw (new ParserException())
                        ->withCode(1635708717)
                        ->withMessage("A quoted path must not be empty")
                        ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                        ->build();
                }
                return [$quotedPathKey];
        }

        throw (new ParserException())
            ->withCode(1635708755)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withParsePathSegment()
            ->build();
    }

    /**
     * PathValue
     *  = ( Literal / DSL_EXPRESSION / FusionObject / EelExpression )
     */
    protected function parsePathValue()
    {
        // watch out for the order, its regex matching and first one wins.
        // sorted by likelihood
        switch (true) {
            case $this->accept(Token::CHAR):
            case $this->accept(Token::STRING):
                return $this->parseStringLiteral();

            case $this->accept(Token::FUSION_OBJECT_NAME):
                return $this->parseFusionObject();

            case $this->accept(Token::DSL_EXPRESSION_START):
                return $this->parseDslExpression();

            case $this->accept(Token::EEL_EXPRESSION):
                return $this->parseEelExpression();

            case $this->accept(Token::FLOAT):
                return (float)$this->consume()->getValue();

            case $this->accept(Token::INTEGER):
                return (int)$this->consume()->getValue();

            case $this->accept(Token::TRUE_VALUE):
                $this->consume();
                return true;

            case $this->accept(Token::FALSE_VALUE):
                $this->consume();
                return false;

            case $this->accept(Token::NULL_VALUE):
                $this->consume();
                return null;
        }

        throw (new ParserException())
            ->withCode(1635708717)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withParsePathValue()
            ->build();
    }

    /**
     * DslExpression
     *  = DSL_EXPRESSION_START DSL_EXPRESSION_CONTENT
     */
    protected function parseDslExpression()
    {
        $dslIdentifier = $this->expect(Token::DSL_EXPRESSION_START)->getValue();
        try {
            $dslCode = $this->expect(Token::DSL_EXPRESSION_CONTENT)->getValue();
        } catch (Fusion\Exception $e) {
            throw (new ParserException())
                ->withCode(1490714685)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->withParseDslExpression()
                ->build();
        }
        $dslCode = substr($dslCode, 1, -1);
        return $this->invokeAndParseDsl($dslIdentifier, $dslCode);
    }

    /**
     * @param string $identifier
     * @param string $code
     * @return array|string|int|float|bool
     * @throws Fusion\Exception
     */
    protected function invokeAndParseDsl(string $identifier, string $code)
    {
        $dslObject = $this->dslFactory->create($identifier);
        try {
            $transpiledFusion = $dslObject->transpile($code);
        } catch (\Exception $e) {
            // convert all exceptions from dsl transpilation to fusion exception and add file and line info
            throw (new ParserException())
                ->withCode(1180600696)
                ->withMessage($e->getMessage())
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->build();
        }

        $parser = new Parser();
        $temporaryAst = $parser->parse('value = ' . $transpiledFusion, $this->contextPathAndFilename, null, false);
        return $temporaryAst['value'];
    }

    /**
     * EelExpression
     *  = EEL_EXPRESSION
     */
    protected function parseEelExpression(): array
    {
        $eelWrapped = $this->expect(Token::EEL_EXPRESSION)->getValue();
        $eelContent = substr($eelWrapped, 2, -1);
        $eelWithoutNewLines = str_replace("\n", '', $eelContent);
        return [
            '__eelExpression' => $eelWithoutNewLines, '__value' => null, '__objectType' => null
        ];
    }

    /**
     * FusionObject
     *  = FUSION_OBJECT_NAME
     */
    protected function parseFusionObject(): array
    {
        return [
            '__objectType' => $this->expect(Token::FUSION_OBJECT_NAME)->getValue(), '__value' => null, '__eelExpression' => null
        ];
    }

    /**
     * Literal
     *  = ( STRING / CHAR )
     *
     */
    protected function parseStringLiteral(): string
    {
        switch (true) {
            case $this->accept(Token::STRING):
                $stringWrapped = $this->consume()->getValue();
                $stringContent = substr($stringWrapped, 1, -1);
                return stripcslashes($stringContent);

            case $this->accept(Token::CHAR):
                $charWrapped = $this->consume()->getValue();
                $charContent = substr($charWrapped, 1, -1);
                return stripslashes($charContent);
        }
        throw new Fusion\Exception('Expected string literal', 1635708719);
    }

    protected function getCurrentObjectPathPrefix(): array
    {
        $lastElementOfStack = end($this->currentObjectPathStack);
        return ($lastElementOfStack === false) ? [] : $lastElementOfStack;
    }

    protected static function throwIfKeyIsReservedParseTreeKey(string $pathKey)
    {
        if (substr($pathKey, 0, 2) === '__'
            && in_array($pathKey, self::$reservedParseTreeKeys, true)) {
            throw new Fusion\Exception("Reversed key '$pathKey' used.", 1437065270);
        }
    }
}
