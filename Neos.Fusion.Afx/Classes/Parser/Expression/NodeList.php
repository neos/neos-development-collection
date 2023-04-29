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
 * Class NodeList
 * @package Neos\Fusion\Afx\Parser\Expression
 */
class NodeList
{
    /**
     * @param Lexer $lexer
     * @return array
     * @throws AfxParserException
     */
    public static function parse(Lexer $lexer): array
    {
        $contents = [];
        $currentText = '';
        while (!$lexer->isEnd()) {
            if ($lexer->isOpeningBracket()) {
                $lexer->consume();
                if ($currentText !== '') {
                    $contents[] = [
                        'type' => 'text',
                        'payload' => $currentText
                    ];
                }
                if ($lexer->isForwardSlash()) {
                    $lexer->rewind();
                    return $contents;
                }
                if ($lexer->isExclamationMark()) {
                    $lexer->rewind();
                    $contents[] = [
                        'type' => 'comment',
                        'payload' => Comment::parse($lexer)
                    ];
                    $currentText = '';
                    continue;
                } else {
                    $lexer->rewind();
                    $contents[] = [
                        'type' => 'node',
                        'payload' => Node::parse($lexer)
                    ];
                    $currentText = '';
                    continue;
                }
            }

            if ($lexer->isOpeningBrace()) {
                if ($currentText) {
                    $contents[] = [
                        'type' => 'text',
                        'payload' => $currentText
                    ];
                }

                $contents[] = [
                    'type' => 'expression',
                    'payload' => Expression::parse($lexer)
                ];
                $currentText = '';
                continue;
            }

            $currentText .= $lexer->consume();
        }

        if ($lexer->isEnd() && $currentText !== '') {
            $contents[] = [
                'type' => 'text',
                'payload' => $currentText
            ];
        }

        return $contents;
    }
}
