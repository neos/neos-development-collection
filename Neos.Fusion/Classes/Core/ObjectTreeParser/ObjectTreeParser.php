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

use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFile;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StatementList;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AbstractStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IncludeStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectStatement;
use Neos\Fusion\Core\ObjectTreeParser\Ast\Block;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AbstractPathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\MetaPathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PrototypePathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\PathSegment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueAssignment;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AbstractPathValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionObjectValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FloatValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\IntValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\BoolValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\NullValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\StringValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueCopy;
use Neos\Fusion\Core\ObjectTreeParser\Ast\AssignedObjectPath;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ValueUnset;
use Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage\MessageCreator;
use Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage\MessageLinePart;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserUnexpectedCharException;

/**
 * Parses a Fusion File to object ast-nodes
 */
class ObjectTreeParser
{
    protected Lexer $lexer;

    protected ?string $contextPathAndFilename;

    protected function __construct(Lexer $lexer, ?string $contextPathAndFilename)
    {
        $this->lexer = $lexer;
        $this->contextPathAndFilename = $contextPathAndFilename;
    }

    public static function parse(FusionSourceCode $fusionCode): FusionFile
    {
        $lexer = new Lexer($fusionCode->getSourceCode());
        $parser = new static($lexer, $fusionCode->getFilePath());
        return $parser->parseFusionFile();
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
            throw new ParserUnexpectedCharException("Expected token: '$tokenReadable'.", 1646988824);
        }
        return $this->lexer->consumeLookahead();
    }

    /**
     * Checks, if the token type matches the current, if so consume it and return true.
     * @param int $tokenType
     * @return bool
     */
    protected function lazyExpect(int $tokenType): bool
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
     * FusionFile
     *  = StatementList
     */
    protected function parseFusionFile(): FusionFile
    {
        try {
            return new FusionFile($this->parseStatementList(), $this->contextPathAndFilename);
        } catch (ParserException $e) {
            throw $e;
        } catch (ParserUnexpectedCharException $e) {
            throw $this->prepareParserException(new ParserException())
                ->setCode($e->getCode())
                ->setMessageCreator(function (MessageLinePart $nextLine) use ($e) {
                    return "Unexpected char {$nextLine->charPrint()}. {$e->getMessage()}";
                })
                ->setPrevious($e)
                ->build();
        } catch (Fusion\Exception $e) {
            throw $this->prepareParserException(new ParserException())
                ->setCode($e->getCode())
                ->setMessage('Exception while parsing: ' . $e->getMessage())
                ->setHideColumnInformation()
                ->setPrevious($e)
                ->build();
        }
    }

    /**
     * StatementList
     *  = ( Statement )*
     *
     * @param ?int $stopLookahead When this tokenType is encountered the loop will be stopped
     */
    protected function parseStatementList(?int $stopLookahead = null): StatementList
    {
        $statements = [];
        $this->lazyBigGap();
        while ($this->accept(Token::EOF) === false
            && ($stopLookahead === null || $this->accept($stopLookahead) === false)) {
            $statements[] = $this->parseStatement();
            $this->lazyBigGap();
        }
        return new StatementList(...$statements);
    }

    /**
     * Statement
     *  = IncludeStatement / ObjectStatement
     */
    protected function parseStatement(): AbstractStatement
    {
        // watch out for the order, its regex matching and first one wins.
        switch (true) {
            case $this->accept(Token::INCLUDE):
                return $this->parseIncludeStatement();

            case $this->accept(Token::PROTOTYPE_START):
            case $this->accept(Token::OBJECT_PATH_PART):
            case $this->accept(Token::META_PATH_START):
            case $this->accept(Token::STRING_SINGLE_QUOTED):
            case $this->accept(Token::STRING_DOUBLE_QUOTED):
                return $this->parseObjectStatement();
        }

        throw $this->prepareParserException(new ParserException())
            ->setCode(1646988828)
            ->setMessageCreator([MessageCreator::class, 'forParseStatement'])
            ->build();
    }

    /**
     * IncludeStatement
     *  = INCLUDE ( STRING / CHAR / FILE_PATTERN ) EndOfStatement
     */
    protected function parseIncludeStatement(): IncludeStatement
    {
        $this->expect(Token::INCLUDE);
        $this->lazyExpect(Token::SPACE);

        switch (true) {
            case $this->accept(Token::STRING_DOUBLE_QUOTED):
            case $this->accept(Token::STRING_SINGLE_QUOTED):
                $stringWrapped = $this->consume()->getValue();
                $filePattern = substr($stringWrapped, 1, -1);
                break;
            case $this->accept(Token::FILE_PATTERN):
                $filePattern = $this->consume()->getValue();
                break;
            default:
                throw new ParserUnexpectedCharException('Expected file pattern in quotes or [a-zA-Z0-9.*:/_-]', 1646988832);
        }

        $this->parseEndOfStatement();

        return new IncludeStatement($filePattern);
    }

    /**
     * ObjectStatement
     *  = ObjectPath ( ValueAssignment / ValueUnset / ValueCopy )? ( Block / EndOfStatement )
     */
    protected function parseObjectStatement(): ObjectStatement
    {
        $currentPath = $this->parseObjectPath();
        $this->lazyExpect(Token::SPACE);
        $cursorAfterObjectPath = $this->lexer->getCursor();

        $operation = match (true) {
            $this->accept(Token::ASSIGNMENT) => $this->parseValueAssignment(),
            $this->accept(Token::UNSET) => $this->parseValueUnset(),
            $this->accept(Token::COPY) => $this->parseValueCopy(),
            default => null
        };

        $this->lazyExpect(Token::SPACE);

        if ($this->accept(Token::LBRACE)) {
            $block = $this->parseBlock();
            return new ObjectStatement($currentPath, $operation, $block, $cursorAfterObjectPath);
        }

        if ($operation === null) {
            throw $this->prepareParserException(new ParserException())
                ->setCode(1646988835)
                ->setMessageCreator([MessageCreator::class, 'forParsePathOrOperator'])
                ->setCursor($cursorAfterObjectPath)
                ->build();
        }

        $this->parseEndOfStatement();
        return new ObjectStatement($currentPath, $operation, null, $cursorAfterObjectPath);
    }

    /**
     * ObjectPath
     *  = PathSegment ( '.' PathSegment )*
     *
     */
    protected function parseObjectPath(): ObjectPath
    {
        $segments = [];
        do {
            $segments[] = $this->parsePathSegment();
        } while ($this->lazyExpect(Token::DOT));
        return new ObjectPath(...$segments);
    }

    /**
     * PathSegment
     *  = ( PROTOTYPE_START FUSION_OBJECT_NAME ')' / OBJECT_PATH_PART / '@' OBJECT_PATH_PART / STRING / CHAR )
     */
    protected function parsePathSegment(): AbstractPathSegment
    {
        switch (true) {
            case $this->accept(Token::PROTOTYPE_START):
                $this->consume();
                try {
                    $prototypeName = $this->expect(Token::FUSION_OBJECT_NAME)->getValue();
                } catch (Fusion\Exception) {
                    throw $this->prepareParserException(new ParserException())
                        ->setCode(1646991578)
                        ->setMessageCreator([MessageCreator::class, 'forPathSegmentPrototypeName'])
                        ->build();
                }
                $this->expect(Token::RPAREN);
                return new PrototypePathSegment($prototypeName);

            case $this->accept(Token::OBJECT_PATH_PART):
                $pathKey = $this->consume()->getValue();
                return new PathSegment($pathKey);

            case $this->accept(Token::META_PATH_START):
                $this->consume();
                $metaPathSegmentKey = $this->expect(Token::OBJECT_PATH_PART)->getValue();
                return new MetaPathSegment($metaPathSegmentKey);

            case $this->accept(Token::STRING_DOUBLE_QUOTED):
            case $this->accept(Token::STRING_SINGLE_QUOTED):
                $stringWrapped = $this->consume()->getValue();
                $quotedPathKey = substr($stringWrapped, 1, -1);
                return new PathSegment($quotedPathKey);
        }

        throw $this->prepareParserException(new ParserException())
            ->setCode(1635708755)
            ->setMessageCreator([MessageCreator::class, 'forParsePathSegment'])
            ->build();
    }

    /**
     * ValueAssignment
     *  = ASSIGNMENT PathValue
     */
    protected function parseValueAssignment(): ValueAssignment
    {
        $this->expect(Token::ASSIGNMENT);
        $this->lazyExpect(Token::SPACE);
        $value = $this->parsePathValue();
        return new ValueAssignment($value);
    }

    /**
     * PathValue
     *  = ( CHAR / STRING / DSL_EXPRESSION / FusionObject / EelExpression )
     */
    protected function parsePathValue(): AbstractPathValue
    {
        // watch out for the order, its regex matching and first one wins.
        // sorted by likelihood
        switch (true) {
            case $this->accept(Token::STRING_SINGLE_QUOTED):
                $charWrapped = $this->consume()->getValue();
                $stringContent = substr($charWrapped, 1, -1);
                return new StringValue(stripslashes($stringContent));

            case $this->accept(Token::STRING_DOUBLE_QUOTED):
                $stringWrapped = $this->consume()->getValue();
                $stringContent = substr($stringWrapped, 1, -1);
                return new StringValue(stripcslashes($stringContent));

            case $this->accept(Token::FUSION_OBJECT_NAME):
                return new FusionObjectValue($this->consume()->getValue());

            case $this->accept(Token::DSL_EXPRESSION_START):
                return $this->parseDslExpression();

            case $this->accept(Token::EEL_EXPRESSION):
                $eelWrapped = $this->consume()->getValue();
                $eelContent = substr($eelWrapped, 2, -1);
                return new EelExpressionValue($eelContent);

            case $this->accept(Token::FLOAT):
                return new FloatValue((float)$this->consume()->getValue());

            case $this->accept(Token::INTEGER):
                return new IntValue((int)$this->consume()->getValue());

            case $this->accept(Token::TRUE_VALUE):
                $this->consume();
                return new BoolValue(true);

            case $this->accept(Token::FALSE_VALUE):
                $this->consume();
                return new BoolValue(false);

            case $this->accept(Token::NULL_VALUE):
                $this->consume();
                return new NullValue();
        }

        throw $this->prepareParserException(new ParserException())
            ->setCode(1646988841)
            ->setMessageCreator([MessageCreator::class, 'forParsePathValue'])
            ->build();
    }

    /**
     * DslExpression
     *  = DSL_EXPRESSION_START DSL_EXPRESSION_CONTENT
     */
    protected function parseDslExpression(): DslExpressionValue
    {
        $dslIdentifier = $this->expect(Token::DSL_EXPRESSION_START)->getValue();
        try {
            $dslCode = $this->expect(Token::DSL_EXPRESSION_CONTENT)->getValue();
        } catch (Fusion\Exception) {
            throw $this->prepareParserException(new ParserException())
                ->setCode(1490714685)
                ->setMessageCreator([MessageCreator::class, 'forParseDslExpression'])
                ->build();
        }
        $dslCode = substr($dslCode, 1, -1);
        return new DslExpressionValue($dslIdentifier, $dslCode);
    }

    /**
     * ValueUnset
     *  = UNSET
     */
    protected function parseValueUnset(): ValueUnset
    {
        $this->expect(Token::UNSET);
        return new ValueUnset();
    }

    /**
     * ValueCopy
     *  = COPY ObjectPathAssignment
     */
    protected function parseValueCopy(): ValueCopy
    {
        $this->expect(Token::COPY);
        $this->lazyExpect(Token::SPACE);
        $sourcePath = $this->parseAssignedObjectPath();
        return new ValueCopy($sourcePath);
    }

    /**
     * AssignedObjectPath
     *  = '.'? ObjectPath
     */
    protected function parseAssignedObjectPath(): AssignedObjectPath
    {
        $isRelative = $this->lazyExpect(Token::DOT);
        return new AssignedObjectPath($this->parseObjectPath(), $isRelative);
    }

    /**
     * Block:
     *  = '{' StatementList? '}'
     */
    protected function parseBlock(): Block
    {
        $this->expect(Token::LBRACE);
        $cursorPositionStartOfBlock = $this->lexer->getCursor() - 1;
        $this->parseEndOfStatement();

        $statementList = $this->parseStatementList(Token::RBRACE);

        try {
            $this->expect(Token::RBRACE);
        } catch (Fusion\Exception) {
            throw $this->prepareParserException(new ParserException())
                ->setCode(1646988844)
                ->setMessage('No closing brace "}" matched this starting block. Encountered <EOF>.')
                ->setCursor($cursorPositionStartOfBlock)
                ->build();
        }

        return new Block($statementList);
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
        throw $this->prepareParserException(new ParserException())
            ->setCode(1635878683)
            ->setMessageCreator([MessageCreator::class, 'forParseEndOfStatement'])
            ->build();
    }

    protected function prepareParserException(ParserException $parserException): ParserException
    {
        return $parserException
            ->setFile($this->contextPathAndFilename)
            ->setFusion($this->lexer->getCode())
            ->setCursor($this->lexer->getCursor());
    }
}
