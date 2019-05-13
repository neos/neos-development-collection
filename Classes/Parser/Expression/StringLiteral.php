<?php
namespace Neos\Fusion\Afx\Parser\Expression;

/*
 * This file is part of the Neos.Fusion.Afx package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Afx\Parser\Exception;
use Neos\Fusion\Afx\Parser\Lexer;

/**
 * Class StringLiteral
 * @package Neos\Fusion\Afx\Parser\Expression
 */
class StringLiteral
{
    public static function parse(Lexer $lexer)
    {
        $openingQuoteSign = '';
        $contents = '';
        $willBeEscaped = false;
        if ($lexer->isSingleQuote() || $lexer->isDoubleQuote()) {
            $openingQuoteSign = $lexer->consume();
        } else {
            throw new Exception('Unquoted String literal');
        }

        while (true) {
            if ($lexer->isEnd()) {
                throw new Exception(sprintf('Unfinished string literal "%s"', $contents));
            }

            if ($lexer->isBackSlash() && !$willBeEscaped) {
                $willBeEscaped = true;
                $lexer->consume();
                continue;
            }

            if ($lexer->isSingleQuote() || $lexer->isDoubleQuote()) {
                $closingQuoteSign = $lexer->consume();
                if (!$willBeEscaped && $openingQuoteSign === $closingQuoteSign) {
                    return $contents;
                }

                $contents .= $closingQuoteSign;
                $willBeEscaped = false;
                continue;
            }

            $contents .= $lexer->consume();
            $willBeEscaped = false;
        }
    }
}
