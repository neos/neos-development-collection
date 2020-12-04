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
 * Class Node
 * @package Neos\Fusion\Afx\Parser\Expression
 */
class Node
{
    /**
     * @param Lexer $lexer
     * @return array
     * @throws AfxParserException
     */
    public static function parse(Lexer $lexer): array
    {
        if ($lexer->isOpeningBracket()) {
            $lexer->consume();
        }

        $identifier = Identifier::parse($lexer);

        try {
            $attributes = [];
            $children = [];

            if ($lexer->isWhitespace()) {
                while ($lexer->isWhitespace()) {
                    $lexer->consume();
                }

                while (!$lexer->isForwardSlash() && !$lexer->isClosingBracket()) {
                    if ($lexer->isOpeningBrace()) {
                        $attributes[] = [
                            'type' => 'spread',
                            'payload' => Spread::parse($lexer)
                        ];
                    } else {
                        $attributes[] = [
                            'type' => 'prop',
                            'payload' => Prop::parse($lexer)
                        ];
                    }
                    while ($lexer->isWhitespace()) {
                        $lexer->consume();
                    }
                }
            }

            if ($lexer->isForwardSlash()) {
                $lexer->consume();

                if ($lexer->isClosingBracket()) {
                    $lexer->consume();

                    return [
                        'identifier' => $identifier,
                        'attributes' => $attributes,
                        'children' => $children,
                        'selfClosing' => true
                    ];
                } else {
                    throw new AfxParserException(sprintf('Self closing tag "%s" misses closing bracket.', $identifier), 1557860567);
                }
            }

            if ($lexer->isClosingBracket()) {
                $lexer->consume();
            } else {
                throw new AfxParserException(sprintf('Tag "%s" did not end with closing bracket.', $identifier), 1557860573);
            }

            $children = NodeList::parse($lexer);

            if ($lexer->isOpeningBracket()) {
                $lexer->consume();

                if ($lexer->isForwardSlash()) {
                    $lexer->consume();
                } else {
                    throw new AfxParserException(sprintf(
                        'Opening-bracket for closing of tag "%s" was not followed by slash.',
                        $identifier
                    ), 1557860584);
                }
            } else {
                throw new AfxParserException(sprintf(
                    'Opening-bracket for closing of tag "%s" expected.',
                    $identifier
                ), 1557860587);
            }

            $closingIdentifier = Identifier::parse($lexer);

            if ($closingIdentifier !== $identifier) {
                throw new AfxParserException(sprintf(
                    'Closing-tag identifier "%s" did not match opening-tag identifier "%s".',
                    $closingIdentifier,
                    $identifier
                ), 1557860595);
            }

            if ($lexer->isClosingBracket()) {
                $lexer->consume();
                return [
                    'identifier' => $identifier,
                    'attributes' => $attributes,
                    'children' => $children,
                    'selfClosing' => false
                ];
            } else {
                throw new AfxParserException(sprintf('Closing tag "%s" did not end with closing-bracket.', $identifier), 1557860618);
            }

            if ($lexer->isEnd()) {
                throw new AfxParserException(sprintf('Tag was %s is not closed.', $identifier), 1557860622);
            }
        } catch (AfxParserException $e) {
            throw new AfxParserException(sprintf('<%s> %s', $identifier, $e->getMessage()), 1557860627);
        }
    }
}
