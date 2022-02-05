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

use Neos\Fusion;

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
use Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage\MessageCreator;
use Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage\MessageLinePart;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserUnexpectedCharException;

/**
 * The Fusion Parser Engine
 */
class PredictiveParser
{
    /**
     * @var Lexer
     */
    protected $lexer;

    /**
     * @var string
     */
    protected $contextPathAndFilename;

    public function __construct(Lexer $lexer, ?string $contextPathAndFilename = null)
    {
        $this->lexer = $lexer;
        $this->contextPathAndFilename = $contextPathAndFilename;
    }

    public function parse(): FusionFileAst
    {
        $statementList = $this->parseFusion();
        return new FusionFileAst($statementList, $this->contextPathAndFilename);
    }

    /**
     * Consume the current token.
     * Can only consume if accept was called before.
     *
     * @return Token
     */
    protected function consume(): Token
    {
        return $this->lexer->consumeLookahead();
    }

    /**
     * Accepts a token of a given type.
     * The Lexer will look up the regex for the token and try to match it on the current string.
     * First match wins.
     *
     * @param int $tokenType
     * @return bool
     */
    protected function accept(int $tokenType): bool
    {
        $token = $this->lexer->getCachedLookaheadOrTryToGenerateLookaheadForTokenAndGetLookahead($tokenType);
        if ($token === null) {
            return false;
        }
        return $token->getType() === $tokenType;
    }

    /**
     * Expects a token of a given type.
     * The Lexer will look up the regex for the token and try to match it on the current string.
     * First match wins.
     *
     * @param int $tokenType
     * @return Token
     * @throws ParserUnexpectedCharException
     */
    protected function expect(int $tokenType): Token
    {
        $token = $this->lexer->getCachedLookaheadOrTryToGenerateLookaheadForTokenAndGetLookahead($tokenType);
        if ($token === null || $token->getType() !== $tokenType) {
            $tokenReadable = Token::typeToString($tokenType);
            throw new ParserUnexpectedCharException("Expected token: '$tokenReadable'.", 1635708717);
        }
        return $this->lexer->consumeLookahead();
    }

    /**
     * Checks, if the token type matches the current, if so consume it and return true.
     * @param int $tokenType
     * @return bool|null
     */
    protected function lazyExpect(int $tokenType): ?bool
    {
        $token = $this->lexer->getCachedLookaheadOrTryToGenerateLookaheadForTokenAndGetLookahead($tokenType);
        if ($token === null || $token->getType() !== $tokenType) {
            return false;
        }
        $this->lexer->consumeLookahead();
        return true;
    }

    /**
     * OptionalBigGap
     *  = ( NEWLINE / OptionalSmallGap )*
     */
    protected function lazyBigGap(): void
    {
        while (true) {
            switch (true) {
                case $this->accept(Token::SPACE):
                case $this->accept(Token::NEWLINE):
                case $this->accept(Token::SLASH_COMMENT):
                case $this->accept(Token::HASH_COMMENT):
                case $this->accept(Token::MULTILINE_COMMENT):
                    $this->consume();
                    break;

                default:
                    return;
            }
        }
    }

    /**
     * OptionalSmallGap
     *  = ( SPACE / SLASH_COMMENT / HASH_COMMENT / MULTILINE_COMMENT )*
     */
    protected function lazySmallGap(): void
    {
        while (true) {
            switch (true) {
                case $this->accept(Token::SPACE):
                case $this->accept(Token::SLASH_COMMENT):
                case $this->accept(Token::HASH_COMMENT):
                case $this->accept(Token::MULTILINE_COMMENT):
                    $this->consume();
                    break;

                default:
                    return;
            }
        }
    }

    /**
     * Fusion
     *  = StatementList
     */
    protected function parseFusion(): StatementListAst
    {
        try {
            return $this->parseStatementList();
        } catch (ParserException $e) {
            throw $e;
        } catch (ParserUnexpectedCharException $e) {
            throw (new ParserException())
                ->withCode($e->getCode())
                ->withMessageCreator(function (MessageLinePart $nextLine) use ($e) {
                    return "Unexpected char {$nextLine->charPrint()}. {$e->getMessage()}";
                })
                ->withPrevious($e)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->build();
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
     * @param ?int $stopLookahead When this tokenType is encountered the loop will be stopped
     */
    protected function parseStatementList(?int $stopLookahead = null): StatementListAst
    {
        $statements = [];
        $this->lazyBigGap();
        while ($this->accept(Token::EOF) === false
            && ($stopLookahead === null || $this->accept($stopLookahead) === false)) {
            $statements[] = $this->parseStatement();
            $this->lazyBigGap();
        }
        return new StatementListAst(...$statements);
    }

    /**
     * Statement
     *  = Include / ObjectDefinition
     */
    protected function parseStatement(): StatementAst
    {
        // watch out for the order, its regex matching and first one wins.
        switch (true) {
            case $this->accept(Token::INCLUDE):
                return $this->parseInclude();

            case $this->accept(Token::PROTOTYPE_START):
            case $this->accept(Token::OBJECT_PATH_PART):
            case $this->accept(Token::META_PATH_START):
            case $this->accept(Token::CHAR):
            case $this->accept(Token::STRING):
                return $this->parseObjectDefinition();
        }

        throw (new ParserException())
            ->withCode(1635708717)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withMessageCreator([MessageCreator::class, 'forParseStatement'])
            ->build();
    }

    /**
     * Include
     *  = INCLUDE ( STRING / CHAR / FILE_PATTERN ) EndOfStatement
     */
    protected function parseInclude(): IncludeAst
    {
        $this->expect(Token::INCLUDE);
        $this->lazyExpect(Token::SPACE);

        switch (true) {
            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $stringWrapped = $this->consume()->getValue();
                $filePattern = substr($stringWrapped, 1, -1);
                break;
            case $this->accept(Token::FILE_PATTERN):
                $filePattern = $this->consume()->getValue();
                break;
            default:
                throw new ParserUnexpectedCharException('Expected file pattern in quotes or [a-zA-Z0-9.*:/_-]', 1635708717);
        }

        $this->parseEndOfStatement();

        return new IncludeAst($filePattern);
    }

    /**
     * ObjectDefinition
     *  = ObjectPath ( ValueAssignment / ValueUnset / ValueCopy )? ( Block / EndOfStatement )
     */
    protected function parseObjectDefinition(): ObjectDefinitionAst
    {
        $currentPath = $this->parseObjectPath();
        $this->lazyExpect(Token::SPACE);
        $cursorAfterObjectPath = $this->lexer->getCursor();

        $operation = null;
        switch (true) {
            case $this->accept(Token::ASSIGNMENT):
                $operation = $this->parseValueAssignment();
                break;

            case $this->accept(Token::UNSET):
                $operation = $this->parseValueUnset();
                break;

            case $this->accept(Token::COPY):
                $operation = $this->parseValueCopy();
                break;
        }
        $this->lazyExpect(Token::SPACE);

        if ($this->accept(Token::LBRACE)) {
            $block = $this->parseBlock();
            return new ObjectDefinitionAst($currentPath, $operation, $block);
        }

        if ($operation === null) {
            throw (new ParserException())
                ->withCode(1635708717)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())
                ->withCursor($cursorAfterObjectPath)
                ->withMessageCreator([MessageCreator::class, 'forParsePathOrOperator'])
                ->build();
        }

        $this->parseEndOfStatement();
        return new ObjectDefinitionAst($currentPath, $operation);
    }

    /**
     * ObjectPath
     *  = PathSegment ( '.' PathSegment )*
     *
     */
    protected function parseObjectPath(): ObjectPathAst
    {
        $segments = [];
        do {
            $segments[] = $this->parsePathSegment();
        } while ($this->lazyExpect(Token::DOT));
        return new ObjectPathAst(...$segments);
    }

    /**
     * PathSegment
     *  = ( PROTOTYPE_START FUSION_OBJECT_NAME ')' / OBJECT_PATH_PART / '@' OBJECT_PATH_PART / STRING / CHAR )
     */
    protected function parsePathSegment(): PathSegmentAst
    {
        switch (true) {
            case $this->accept(Token::PROTOTYPE_START):
                $this->consume();
                $prototypeName = $this->expect(Token::FUSION_OBJECT_NAME)->getValue();
                $this->expect(Token::RPAREN);
                return new PrototypePathAst($prototypeName);

            case $this->accept(Token::OBJECT_PATH_PART):
                $pathKey = $this->consume()->getValue();
                return new ObjectPathPartAst($pathKey);

            case $this->accept(Token::META_PATH_START):
                $this->consume();
                $metaPathKey = $this->expect(Token::OBJECT_PATH_PART)->getValue();
                return new MetaPathAst($metaPathKey);

            case $this->accept(Token::STRING):
            case $this->accept(Token::CHAR):
                $stringWrapped = $this->consume()->getValue();
                $quotedPathKey = substr($stringWrapped, 1, -1);
                if ($quotedPathKey === '') {
                    throw (new ParserException())
                        ->withCode(1635708717)
                        ->withMessage("A quoted path must not be empty")
                        ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                        ->build();
                }
                return new ObjectPathPartAst($quotedPathKey);
        }

        throw (new ParserException())
            ->withCode(1635708755)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withMessageCreator([MessageCreator::class, 'forParsePathSegment'])
            ->build();
    }

    /**
     * ValueAssignment
     *  = ASSIGNMENT PathValue
     */
    protected function parseValueAssignment(): ValueAssignmentAst
    {
        $this->expect(Token::ASSIGNMENT);
        $this->lazyExpect(Token::SPACE);
        $value = $this->parsePathValue();
        return new ValueAssignmentAst($value);
    }

    /**
     * PathValue
     *  = ( CHAR / STRING / DSL_EXPRESSION / FusionObject / EelExpression )
     */
    protected function parsePathValue(): PathValueAst
    {
        // watch out for the order, its regex matching and first one wins.
        // sorted by likelihood
        switch (true) {
            case $this->accept(Token::CHAR):
                $charWrapped = $this->consume()->getValue();
                $charContent = substr($charWrapped, 1, -1);
                return new CharValueAst($charContent);

            case $this->accept(Token::STRING):
                $stringWrapped = $this->consume()->getValue();
                $stringContent = substr($stringWrapped, 1, -1);
                return new StringValueAst($stringContent);

            case $this->accept(Token::FUSION_OBJECT_NAME):
                return new FusionObjectValueAst($this->consume()->getValue());

            case $this->accept(Token::DSL_EXPRESSION_START):
                return $this->parseDslExpression();

            case $this->accept(Token::EEL_EXPRESSION):
                $eelWrapped = $this->consume()->getValue();
                $eelContent = substr($eelWrapped, 2, -1);
                return new EelExpressionValueAst($eelContent);

            case $this->accept(Token::FLOAT):
                return new SimpleValueAst((float)$this->consume()->getValue());

            case $this->accept(Token::INTEGER):
                return new SimpleValueAst((int)$this->consume()->getValue());

            case $this->accept(Token::TRUE_VALUE):
                $this->consume();
                return new SimpleValueAst(true);

            case $this->accept(Token::FALSE_VALUE):
                $this->consume();
                return new SimpleValueAst(false);

            case $this->accept(Token::NULL_VALUE):
                $this->consume();
                return new SimpleValueAst(null);
        }

        throw (new ParserException())
            ->withCode(1635708717)
            ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
            ->withMessageCreator([MessageCreator::class, 'forParsePathValue'])
            ->build();
    }

    /**
     * DslExpression
     *  = DSL_EXPRESSION_START DSL_EXPRESSION_CONTENT
     */
    protected function parseDslExpression(): DslExpressionValueAst
    {
        $dslIdentifier = $this->expect(Token::DSL_EXPRESSION_START)->getValue();
        try {
            $dslCode = $this->expect(Token::DSL_EXPRESSION_CONTENT)->getValue();
        } catch (Fusion\Exception $e) {
            throw (new ParserException())
                ->withCode(1490714685)
                ->withFile($this->contextPathAndFilename)->withFusion($this->lexer->getCode())->withCursor($this->lexer->getCursor())
                ->withMessageCreator([MessageCreator::class, 'forParseDslExpression'])
                ->build();
        }
        $dslCode = substr($dslCode, 1, -1);
        return new DslExpressionValueAst($dslIdentifier, $dslCode);
    }

    /**
     * ValueUnset
     *  = UNSET
     */
    protected function parseValueUnset(): ValueUnsetAst
    {
        $this->expect(Token::UNSET);
        return new ValueUnsetAst();
    }

    /**
     * ValueCopy
     *  = COPY ObjectPathAssignment
     */
    protected function parseValueCopy(): ValueCopyAst
    {
        $this->expect(Token::COPY);
        $this->lazyExpect(Token::SPACE);
        $sourcePath = $this->parseAssignedObjectPath();
        return new ValueCopyAst($sourcePath);
    }

    /**
     * AssignedObjectPath
     *  = '.'? ObjectPath
     */
    protected function parseAssignedObjectPath(): AssignedObjectPathAst
    {
        $isRelative = $this->lazyExpect(Token::DOT);
        return new AssignedObjectPathAst($this->parseObjectPath(), $isRelative);
    }

    /**
     * Block:
     *  = '{' StatementList? '}'
     */
    protected function parseBlock(): BlockAst
    {
        $this->expect(Token::LBRACE);
        $cursorPositionStartOfBlock = $this->lexer->getCursor() - 1;
        $this->parseEndOfStatement();

        $statementList = $this->parseStatementList(Token::RBRACE);

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

        return new BlockAst($statementList);
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
            ->withMessageCreator([MessageCreator::class, 'forParseEndOfStatement'])
            ->build();
    }
}
