<?php
declare(strict_types=1);

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

use Neos\Fusion\Afx\Parser\AfxParserException;
use Neos\Fusion\Afx\Parser\Lexer;

/**
 * Class Spread
 * @package Neos\Fusion\Afx\Parser\Expression
 */
class Spread
{
    /**
     * @param Lexer $lexer
     * @return array
     * @throws AfxParserException
     */
    public static function parse(Lexer $lexer): array
    {
        $contents = '';
        $braceCount = 0;

        if ($lexer->isOpeningBrace() && $lexer->peek(4) === '{...') {
            $lexer->consume();
            $lexer->consume();
            $lexer->consume();
            $lexer->consume();
        } else {
            throw new AfxParserException('Spread without braces', 1557860522);
        }

        while (true) {
            if ($lexer->isEnd()) {
                throw new AfxParserException(sprintf('Unfinished Spread "%s"', $contents), 1557860526);
            }

            if ($lexer->isOpeningBrace()) {
                $braceCount++;
            }

            if ($lexer->isClosingBrace()) {
                if ($braceCount === 0) {
                    $lexer->consume();
                    return [
                        'type' => 'expression',
                        'payload' => $contents
                    ];
                }

                $braceCount--;
            }

            $contents .= $lexer->consume();
        }
    }
}
