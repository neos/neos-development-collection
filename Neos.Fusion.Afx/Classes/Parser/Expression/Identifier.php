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

class Identifier
{
    public static function parse(Lexer $lexer)
    {
        $identifier = '';

        while (true) {
            switch (true) {
                case $lexer->isAlphaNumeric():
                case $lexer->isDot():
                case $lexer->isColon():
                case $lexer->isMinus():
                case $lexer->isUnderscore():
                case $lexer->isAt():
                    $identifier .= $lexer->consume();
                    break;
                case $lexer->isEqualSign():
                case $lexer->isWhiteSpace():
                case $lexer->isClosingBracket():
                case $lexer->isForwardSlash():
                    return $identifier;
                    break;
                default:
                    $unexpected_character = $lexer->consume();
                    throw new Exception(sprintf(
                        'Unexpected character "%s" in identifier "%s"',
                        $unexpected_character,
                        $identifier
                    ));
            }
        }
    }
}
