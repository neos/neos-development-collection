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

class Spread
{
    public static function parse(Lexer $lexer)
    {
        $contents = '';
        $braceCount = 0;

        if ($lexer->isOpeningBrace() && $lexer->peek(4) === '{...') {
            $lexer->consume();
            $lexer->consume();
            $lexer->consume();
            $lexer->consume();
        } else {
            throw new Exception('Spread without braces');
        }

        while (true) {
            if ($lexer->isEnd()) {
                throw new Exception(sprintf('Unfinished Spread "%s"', $contents));
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
