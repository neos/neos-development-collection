<?php

declare(strict_types=1);

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Afx\Parser\AfxParserException;
use Neos\Fusion\Afx\Parser\Parser as AfxParser;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class EelExpressionTransformer
{
    private function __construct(private readonly string $fileContent, private readonly string $commentPrefix, private readonly \Closure $onWarning)
    {
    }

    public static function forContent(string $fileContent, string $commentPrefix, \Closure $onWarning): self
    {
        return new self($fileContent, $commentPrefix, $onWarning);
    }

    /**
     * @throws ParserException
     * @throws AfxParserException
     */
    public function process(\Closure $processingFunction): self
    {
        $eelExpressions = $this->findAllEelExpressions();

        // apply processing function on Eel expressions
        $eelExpressions = $eelExpressions->map(
            fn (EelExpressionPosition $expressionPosition) => $expressionPosition->withEelExpression(
                $processingFunction($expressionPosition->eelExpression, $expressionPosition->fusionPath)
            )
        );

        return new self($this->render($eelExpressions), $this->commentPrefix, $this->onWarning);
    }

    /**
     * @param RegexCommentTemplatePair[] $regexCommentTemplatePairs
     * @throws AfxParserException
     * @throws ParserException
     */
    public function addCommentsIfRegexesMatch(array $regexCommentTemplatePairs): self
    {
        $eelExpressions = $this->findAllEelExpressions();

        $comments = [];
        // fill $comments
        foreach ($regexCommentTemplatePairs as $regexCommentTemplatePair) {
            $eelExpressions->map(
                function (EelExpressionPosition $expressionPosition) use ($regexCommentTemplatePair, &$comments) {
                    $comments = array_merge(
                        $comments,
                        $this->getPrecedingCommentsForEelExpressionPosition($expressionPosition, $regexCommentTemplatePair->regex, $regexCommentTemplatePair->template)
                    );
                    return $expressionPosition;
                }
            );
        }

        $comments = $this->replaceLinePlaceholderWithinCommentTemplates($comments);

        foreach ($comments as $comment) {
            ($this->onWarning)($comment->text);
        }

        $precedingComments = array_map(fn ($comment) => '// ' . $this->commentPrefix . $comment->text, $comments);

        if (count($precedingComments)) {
            if (str_contains($this->fileContent, '// ' . $this->commentPrefix) || str_contains($this->fileContent, '// TODO 9.0 migration:')) {
                ($this->onWarning)('No migration todo comments written as the migration was already run.');
                return $this;
            }
            return new self(implode("\n", $precedingComments) . "\n" . $this->fileContent, $this->commentPrefix, $this->onWarning);
        } else {
            return $this;
        }
    }

    /** @return PrecedingFusionFileComment[] */
    private function getPrecedingCommentsForEelExpressionPosition(EelExpressionPosition $expressionPosition, string $regex, string $template): array
    {
        $comments = [];

        $matches = [];
        if (preg_match_all($regex, $expressionPosition->eelExpression, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                // $match[0][0] => the fully matched string
                // $match[0][1] => the start offset of the string
                $offsetOfFullMatch = $match[0][1];
                $lineNumberOfMatch = substr_count($this->fileContent, "\n", 0, $expressionPosition->fromOffset + $offsetOfFullMatch);
                // we add one because the number of counted new line characters will be one less than the actual line number
                $lineNumberOfMatch += 1;
                // avoid adding the same comment multiple times per line by using an explicit array key
                $commentKey = sha1($lineNumberOfMatch . $regex . $template);
                $comments[$commentKey] = new PrecedingFusionFileComment($lineNumberOfMatch, $template);
            }
        }

        return $comments;
    }

    /**
     * @param PrecedingFusionFileComment[] $comments
     * @return PrecedingFusionFileComment[]
     */
    private function replaceLinePlaceholderWithinCommentTemplates(array $comments): array
    {
        foreach ($comments as $comment) {
            $finalLineNumber = $comment->lineNumberOfMatch + count($comments);
            $comment->text = str_replace('%LINE', (string)$finalLineNumber, $comment->template);
        }
        return $comments;
    }

    /**
     * @throws ParserException
     * @throws AfxParserException
     */
    private function findAllEelExpressions(): EelExpressionPositions
    {
        try {
            $eelExpressions = ExpressionCollectingObjectTreeParser::findEelExpressions($this->fileContent);
            $afxExpressions = ExpressionCollectingObjectTreeParser::findAfxExpressions($this->fileContent);
        } catch (ParserException $exception) {
            ($this->onWarning)($exception->getMessage());
            return EelExpressionPositions::fromArray([]);
        }

        foreach ($afxExpressions as $afxExpression) {
            $parser = new AfxParser($afxExpression->code);
            $ast = $parser->parse();
            $eelExpressionsInAfx = self::findEelExpressionsInAfxAst($ast);
            $eelExpressionsInAfx = $eelExpressionsInAfx->withOffset($afxExpression->fromOffset);

            $eelExpressions = $eelExpressions->addAndSort($eelExpressionsInAfx);
        }
        return $eelExpressions;
    }

    /**
     * @param array $afxAst
     * @return EelExpressionPositions
     */
    private static function findEelExpressionsInAfxAst(array $afxAst): EelExpressionPositions
    {
        $result = [];
        foreach ($afxAst as $afxElement) {
            self::findEelExpressionsInAfxAstElement($afxElement, $result);
        }
        return EelExpressionPositions::fromArray($result);
    }

    /**
     * @param array $afxElement
     * @param EelExpressionPosition[] $result
     * @return void
     */
    private static function findEelExpressionsInAfxAstElement(array $afxElement, array &$result): void
    {
        if ($afxElement['type'] === 'text') {
            return;
        }
        if ($afxElement['type'] === 'string') {
            return;
        }
        if ($afxElement['type'] === 'node') {
            foreach ($afxElement['payload']['attributes'] as $attribute) {
                self::findEelExpressionsInAfxAstElement($attribute, $result);
            }
            foreach ($afxElement['payload']['children'] as $child) {
                self::findEelExpressionsInAfxAstElement($child, $result);
            }
        }
        if ($afxElement['type'] === 'prop') {
            self::findEelExpressionsInAfxAstElement($afxElement['payload'], $result);
        }
        if ($afxElement['type'] === 'spread') {
            // We found an Eel expression in a spread operation
            $result[] = new EelExpressionPosition(
                $afxElement['payload']['payload'],
                $afxElement['from'] + 1,
                $afxElement['to'] + 2,
                null
            );
            return;
        }

        if ($afxElement['type'] === 'expression') {
            // We found an Eel expression
            $result[] = new EelExpressionPosition(
                $afxElement['payload'],
                $afxElement['from'] + 1,
                $afxElement['to'] + 2,
                null
            );
        }
    }

    private function render(EelExpressionPositions $eelExpressions): string
    {
        if ($eelExpressions->isEmpty()) {
            return $this->fileContent;
        }

        // Render [fusion] [1st eel expression]
        $processedFusionString = substr($this->fileContent, 0, $eelExpressions->first()->fromOffset);
        $processedFusionString .= $eelExpressions->first()->eelExpression;
        $toOffsetOfLastSeenEelExpression = $eelExpressions->first()->toOffset;

        // Render all Eel expressions (2...n)
        foreach ($eelExpressions->withoutFirst() as $eelExpression) {
            $processedFusionString .= substr($this->fileContent, $toOffsetOfLastSeenEelExpression, $eelExpression->fromOffset - $toOffsetOfLastSeenEelExpression);
            $processedFusionString .= $eelExpression->eelExpression;
            $toOffsetOfLastSeenEelExpression = $eelExpression->toOffset;
        }
        // render remaining Fusion in File
        $processedFusionString .= substr($this->fileContent, $toOffsetOfLastSeenEelExpression);

        return $processedFusionString;
    }

    public function getProcessedContent(): string
    {
        return $this->fileContent;
    }
}
