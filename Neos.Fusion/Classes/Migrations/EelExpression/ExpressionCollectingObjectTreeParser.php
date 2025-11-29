<?php

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Fusion\Core\ObjectTreeParser\Ast\AbstractPathValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\DslExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;
use Neos\Fusion\Core\ObjectTreeParser\Lexer;
use Neos\Fusion\Core\ObjectTreeParser\ObjectTreeParser;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class ExpressionCollectingObjectTreeParser extends ObjectTreeParser
{
    /**
     * @var EelExpressionPosition[]
     */
    private array $foundEelExpressions = [];

    /**
     * @var AfxExpressionPosition[]
     */
    private array $foundAfxExpressions = [];

    /**
     * @throws ParserException
     */
    public static function findEelExpressions(string $sourceCode, ?string $contextPathAndFilename = null): EelExpressionPositions
    {
        $lexer = new Lexer($sourceCode);
        $parser = new self($lexer, $contextPathAndFilename);
        $fusionFile = $parser->parseFusionFile();
        $eelExpressionPositions = EelExpressionPositions::fromArray($parser->foundEelExpressions);

        // enrich $eelExpressionPositions by filling fusionPath -> needed for some context sensitive transformations
        $eelExpressionPathBuilder = new EelExpressionPathBuilderVisitor(
            $eelExpressionPositions
        );
        $fusionFile->visit($eelExpressionPathBuilder);

        return $eelExpressionPositions;
    }

    /**
     * @return AfxExpressionPosition[]
     * @throws ParserException
     */
    public static function findAfxExpressions(string $sourceCode, ?string $contextPathAndFilename = null): array
    {
        $lexer = new Lexer($sourceCode);
        $parser = new self($lexer, $contextPathAndFilename);
        $parser->parseFusionFile();
        return $parser->foundAfxExpressions;
    }

    /**
     * @throws ParserException
     */
    protected function parsePathValue(): AbstractPathValue
    {
        $fromOffset = $this->lexer->getCursor();
        $result = parent::parsePathValue();
        if ($result instanceof EelExpressionValue) {
            $toOffset = $this->lexer->getCursor();
            $this->foundEelExpressions[] = new EelExpressionPosition($result->value, $fromOffset + 2, $toOffset - 1, $result);
        }
        return $result;
    }

    /**
     * @throws ParserException
     */
    protected function parseDslExpression(): DslExpressionValue
    {
        $fromOffset = $this->lexer->getCursor();
        $result = parent::parseDslExpression();
        $toOffset = $this->lexer->getCursor();

        if ($result->identifier === 'afx') {
            $this->foundAfxExpressions[] = new AfxExpressionPosition($result->code, $fromOffset, $toOffset);
        }
        return $result;
    }
}
