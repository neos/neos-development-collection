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
 * Class Prop
 * @package Neos\Fusion\Afx\Parser\Expression
 */
class Prop
{
    /**
     * @param Lexer $lexer
     * @return array
     * @throws AfxParserException
     */
    public static function parse(Lexer $lexer): array
    {
        $identifier = Identifier::parse($lexer);

        if ($lexer->isEqualSign()) {
            $lexer->consume();
            switch (true) {
                case $lexer->isSingleQuote():
                case $lexer->isDoubleQuote():
                    $value = [
                        'type' => 'string',
                        'payload' => StringLiteral::parse($lexer),
                        'identifier' => $identifier
                    ];
                    break;

                case $lexer->isOpeningBrace():
                    $value = [
                        'type' => 'expression',
                        'payload' => Expression::parse($lexer),
                        'identifier' => $identifier
                    ];
                    break;
                default:
                    throw new AfxParserException(sprintf(
                        'Prop-assignment "%s" was not followed by quotes or braces',
                        $identifier
                    ), 1557860545);
            }
        } elseif ($lexer->isWhiteSpace() || $lexer->isForwardSlash() || $lexer->isClosingBracket()) {
            $value = [
                'type' => 'boolean',
                'payload' => true,
                'identifier' => $identifier
            ];
        } else {
            throw new AfxParserException(sprintf('Prop identifier "%s" is neither assignment nor boolean', $identifier), 1557860552);
        }

        return $value;
    }
}
