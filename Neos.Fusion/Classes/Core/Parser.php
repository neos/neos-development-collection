<?php

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
use Neos\Fusion\Exception;

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
     * @param string $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further Fusion files
     * @param array|AstBuilder|null $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @param boolean $buildPrototypeHierarchy Merge prototype configurations or not. Will be false for includes to only do that once at the end.
     * @return array A Fusion object tree, generated from the source code
     * @throws Fusion\Exception|ParserException
     * @api
     */
    public function parse($sourceCode, $contextPathAndFilename = null, $objectTreeUntilNow = null, bool $buildPrototypeHierarchy = true): array
    {
        if (\is_string($sourceCode) === false) {
            throw new Fusion\Exception('Cannot parse Fusion - $sourceCode must be of type string!', 1180203775);
        }

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
            throw new ParserException(ParserException::MESSAGE_FROM_INPUT | ParserException::HIDE_COLUMN, $this->getParsingContext(), $e->getCode(), 'Exception while parsing: ' . $e->getMessage(), $e);
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
     *  = IncludeStatement / PrototypeDeclaration / UnsetStatement / ObjectDefinition
     */
    protected function parseStatement(): void
    {
        switch (true) {
            // watch out for the order, its regex matching and first one wins.
            case $this->accept(Token::INCLUDE):
                $this->parseIncludeStatement();
                return;

            case $this->accept(Token::PROTOTYPE):
                $this->parsePrototypeDeclaration();
                return;

            case $this->accept(Token::UNSET_KEYWORD):
                $this->parseUnsetStatement();
                return;

            case $this->accept(Token::PROTOTYPE_START):
            case $this->accept(Token::OBJECT_PATH_PART):
            case $this->accept(Token::META_PATH_START):
            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $this->parseObjectDefinition();
                return;
        }

        throw new ParserException(ParserException::MESSAGE_PARSING_STATEMENT, $this->getParsingContext(), 1635708717);
    }

    /**
     * PrototypeDeclaration
     *  = PROTOTYPE FusionObjectName ( EXTENDS FusionObjectName )? ( BlockStatement / EndOfStatement )
     */
    protected function parsePrototypeDeclaration(): void
    {
        $this->expect(Token::PROTOTYPE);
        $this->lazySmallGap();
        $currentPathPrefix = $this->getCurrentObjectPathPrefix();

        $currentPath = $currentPathPrefix;
        array_push($currentPath, '__prototypes', $this->parseFusionObjectName());

        $this->lazySmallGap();

        if ($prototypeWasExtended = $this->lazyExpect(Token::EXTENDS)) {
            $this->lazySmallGap();
            $extendObjectPath = $currentPathPrefix;
            array_push($extendObjectPath, '__prototypes', $this->parseFusionObjectName());
            $this->astBuilder->inheritPrototypeInObjectTree($currentPath, $extendObjectPath);
            $this->lazySmallGap();
        }

        if ($this->accept(Token::LBRACE)) {
            $this->parseBlockStatement($currentPath);
            return;
        }

        if ($prototypeWasExtended === true) {
            $this->parseEndOfStatement();
            return;
        }

        throw new ParserException(ParserException::MESSAGE_FROM_INPUT | ParserException::MESSAGE_UNEXPECTED_CHAR, $this->getParsingContext(), 1635708717, 'Syntax error while parsing prototype declaration');
    }

    /**
     * UnsetStatement
     *  = UNSET_KEYWORD ObjectPathAssignment EndOfStatement
     */
    protected function parseUnsetStatement(): void
    {
        $this->expect(Token::UNSET_KEYWORD);
        $this->lazySmallGap();
        $currentPath = $this->parseAssignedObjectPath($this->getCurrentObjectPathPrefix());
        $this->astBuilder->removeValueInObjectTree($currentPath);
        $this->parseEndOfStatement();
    }

    /**
     * ValueAssignment
     *  = ASSIGNMENT PathValue
     */
    protected function parseValueAssignment($currentPath): void
    {
        $this->expect(Token::ASSIGNMENT);
        $this->lazySmallGap();
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
     *  = ( COPY / EXTENDS ) ObjectPathAssignment
     */
    protected function parseValueCopy($currentPath): void
    {
        if ($this->accept(Token::COPY) === false
            && $this->accept(Token::EXTENDS) === false) {
            throw new Exception("Error Processing Request", 1);
        }
        $operator = $this->consume()->getType();

        $this->lazySmallGap();
        $sourcePath = $this->parseAssignedObjectPath(AstBuilder::getParentPath($currentPath));

        $currentPathsPrototype = AstBuilder::objectPathIsPrototype($currentPath);
        $sourcePathIsPrototype = AstBuilder::objectPathIsPrototype($sourcePath);
        if ($currentPathsPrototype && $sourcePathIsPrototype) {
            // both are a prototype definition
            try {
                $this->astBuilder->inheritPrototypeInObjectTree($currentPath, $sourcePath);
            } catch (Fusion\Exception $e) {
                // delay throw since there might be syntax errors causing this.
                $this->delayedCombinedException = new ParserException(
                    ParserException::MESSAGE_FROM_INPUT | ParserException::HIDE_COLUMN,
                    $this->getParsingContext(),
                    $e->getStatusCode(),
                    $e->getMessage(),
                    $this->delayedCombinedException
                );
            }
            return;
        }

        if ($currentPathsPrototype || $sourcePathIsPrototype) {
            // Only one of "source" or "target" is a prototype. We do not support copying a
            // non-prototype value to a prototype value or vice-versa.
            // delay throw since there might be syntax errors causing this.
            $this->delayedCombinedException = new ParserException(
                ParserException::MESSAGE_FROM_INPUT | ParserException::HIDE_COLUMN,
                $this->getParsingContext(),
                1358418015,
                "Cannot inherit, when one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.",
                $this->delayedCombinedException
            );
            return;
        }

        if ($operator === Token::EXTENDS) {
            throw new ParserException(ParserException::MESSAGE_FROM_INPUT | ParserException::HIDE_COLUMN, $this->getParsingContext(), 1635708717, "The operator 'extends' doesnt support the copy path operation");
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
        $this->lazySmallGap();
        $exceptionContextAfterPath = $this->getParsingContext();

        $operationWasParsed = null;
        switch (true) {
            case $this->accept(Token::ASSIGNMENT):
                $this->parseValueAssignment($currentPath);
                break;

            case $this->accept(Token::UNSET):
                $this->parseValueUnset($currentPath);
                break;

            case $this->accept(Token::COPY):
            case $this->accept(Token::EXTENDS):
                $this->parseValueCopy($currentPath);
                break;

            default:
                $operationWasParsed = false;
        }

        $this->lazySmallGap();

        if ($this->accept(Token::LBRACE)) {
            $this->parseBlockStatement($currentPath);
            return;
        }

        if ($operationWasParsed !== false) {
            $this->parseEndOfStatement();
            return;
        }
        throw new ParserException(ParserException::MESSAGE_PARSING_PATH_OR_OPERATOR, $exceptionContextAfterPath, 1635708717);
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
            case $this->accept(Token::REST_OF_LINE):
                $filePattern = $this->consume()->getValue();
                break;
            default:
                throw new ParserException(ParserException::MESSAGE_UNEXPECTED_CHAR | ParserException::MESSAGE_FROM_INPUT, $this->getParsingContext(), 1635708717, 'Expected file pattern in quotes or [a-zA-Z0-9.*:/_-]');
        }

        try {
            $this->includeAndParseFilesByPattern($filePattern);
        } catch (ParserException $e) {
            throw $e;
        } catch (Fusion\Exception $e) {
            throw new ParserException(ParserException::MESSAGE_FROM_INPUT | ParserException::HIDE_COLUMN, $this->getParsingContext(), $e->getCode(), $e->getMessage());
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
     *  = ( EOF / ; / NEWLINE )
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

        throw new ParserException(ParserException::MESSAGE_PARSING_END_OF_STATEMENT, $this->getParsingContext(), 1635878683);
    }

    /**
     * BlockStatement:
     *  = { StatementList? }
     */
    protected function parseBlockStatement(array $path): void
    {

        $this->expect(Token::LBRACE);
        array_push($this->currentObjectPathStack, $path);
        $exceptionContextStartOfBlock = $this->getParsingContext(-1);

        $this->parseStatementList(Token::RBRACE);

        try {
            $this->expect(Token::RBRACE);
        } catch (Fusion\Exception $e) {
            throw new ParserException(ParserException::MESSAGE_FROM_INPUT, $exceptionContextStartOfBlock, 1635708717, 'No closing brace "}" matched this starting block. Encountered <EOF>.');
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
     *  = ( PROTOTYPE_START FusionObjectName ) / OBJECT_PATH_PART / @ OBJECT_PATH_PART / StringLiteral )
     */
    protected function parsePathSegment(): array
    {
        switch (true) {
            case $this->accept(Token::PROTOTYPE_START):
                $this->consume();
                $prototypeName = $this->parseFusionObjectName();
                $this->expect(Token::RPAREN);
                return ['__prototypes', $prototypeName];

            case $this->accept(Token::OBJECT_PATH_PART):
                $value = $this->consume()->getValue();
                self::throwIfKeyIsReservedParseTreeKey($value);
                return [$value];

            case $this->accept(Token::META_PATH_START):
                $this->consume();
                $metaName = $this->expect(Token::OBJECT_PATH_PART)->getValue();
                $metaName = $metaName === 'override' ? 'context' : $metaName;
                return ['__meta', $metaName];

            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $value = $this->parseStringLiteral();
                if ($value === '') {
                    throw new ParserException(ParserException::MESSAGE_FROM_INPUT, $this->getParsingContext(), 1635708717, "A quoted path must not be empty");
                }
                return [$value];
        }

        throw new ParserException(ParserException::MESSAGE_PARSING_PATH_SEGMENT, $this->getParsingContext(), 1635708755);
    }

    /**
     * FusionObjectName
     *  = FUSION_OBJECT_NAME
     */
    protected function parseFusionObjectName(): string
    {
        return $this->expect(Token::FUSION_OBJECT_NAME)->getValue();
    }

    /**
     * PathValue
     *  = ( Literal / DSL_EXPRESSION / FusionObject / EelExpression )
     */
    protected function parsePathValue()
    {
        switch (true) {
            case $this->accept(Token::FUSION_OBJECT_NAME):
                return $this->parseFusionObject();

            // watch out for the order, its regex matching and first one wins.
            case $this->accept(Token::FALSE_VALUE):
            case $this->accept(Token::NULL_VALUE):
            case $this->accept(Token::TRUE_VALUE):
            case $this->accept(Token::FLOAT):
            case $this->accept(Token::INTEGER):
            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                return $this->parseLiteral();

            case $this->accept(Token::DSL_EXPRESSION_START):
                return $this->parseDslExpression();

            case $this->accept(Token::EEL_EXPRESSION):
                return $this->parseEelExpression();
        }
        throw new ParserException(ParserException::MESSAGE_PARSING_VALUE_ASSIGNMENT, $this->getParsingContext(), 1635708717);
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
            throw new ParserException(ParserException::MESSAGE_PARSING_DSL_EXPRESSION, $this->getParsingContext(), 1490714685);
        }
        $dslCode = substr($dslCode, 1, -1);
        return $this->invokeAndParseDsl($dslIdentifier, $dslCode);
    }

    /**
     * @param string $identifier
     * @param $code
     * @return mixed
     * @throws Exception
     * @throws Fusion\Exception
     */
    protected function invokeAndParseDsl(string $identifier, $code)
    {
        $dslObject = $this->dslFactory->create($identifier);
        try {
            $transpiledFusion = $dslObject->transpile($code);
        } catch (\Exception $e) {
            // convert all exceptions from dsl transpilation to fusion exception and add file and line info
            throw new ParserException(ParserException::MESSAGE_FROM_INPUT, $this->getParsingContext(), 1180600696, $e->getMessage());
        }

        $parser = new Parser();
        $temporaryAst = $parser->parse('value = ' . $transpiledFusion);
        return $temporaryAst['value'];
    }

    /**
     * EelExpression
     *  = EEL_EXPRESSION
     */
    protected function parseEelExpression(): array
    {
        $eelExpression = $this->expect(Token::EEL_EXPRESSION)->getValue();
        $eelExpression = substr($eelExpression, 2, -1);

        $eelExpression = str_replace("\n", '', $eelExpression);
        return [
            '__eelExpression' => $eelExpression, '__value' => null, '__objectType' => null
        ];
    }

    /**
     * FusionObject
     *  = FusionObjectName
     */
    protected function parseFusionObject(): array
    {
        return [
            '__objectType' => $this->parseFusionObjectName(), '__value' => null, '__eelExpression' => null
        ];
    }

    /**
     * Literal
     *  = ( TRUE_VALUE / FALSE_VALUE / NULL_VALUE / STRING / CHAR / INTEGER / FLOAT )
     *
     * @return mixed
     */
    protected function parseLiteral()
    {
        switch (true) {
            case $this->accept(Token::TRUE_VALUE):
                $this->consume();
                return true;
            case $this->accept(Token::FALSE_VALUE):
                $this->consume();
                return false;
            case $this->accept(Token::NULL_VALUE):
                $this->consume();
                return null;

            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                return $this->parseStringLiteral();

            case $this->accept(Token::INTEGER):
                return (int)$this->consume()->getValue();
            case $this->accept(Token::FLOAT):
                return (float)$this->consume()->getValue();
        }
        throw new ParserException(ParserException::MESSAGE_UNEXPECTED_CHAR | ParserException::MESSAGE_FROM_INPUT, $this->getParsingContext(), 1635708717, 'Expected literal.');
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
                $string = $this->consume()->getValue();
                $string = substr($string, 1, -1);
                return stripcslashes($string);

            case $this->accept(Token::CHAR):
                $char = $this->consume()->getValue();
                $char = substr($char, 1, -1);
                return stripslashes($char);
        }
        throw new ParserException(ParserException::MESSAGE_UNEXPECTED_CHAR | ParserException::MESSAGE_FROM_INPUT, $this->getParsingContext(), 1635708719, 'Expected string literal');
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
            throw new Fusion\Exception(sprintf('Reversed key "%s" used.', $pathKey), 1437065270);
        }
    }
}
