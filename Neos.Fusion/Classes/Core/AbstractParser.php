<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

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

abstract class AbstractParser
{
    /**
     * @var Lexer
     */
    protected $lexer;

    /**
     * Consume the current token.
     * Can only consume if accept was called before.
     *
     * @return Token
     */
    protected function consume(): Token
    {
        return  $this->lexer->consumeLookahead();
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
     * @throws Fusion\Exception
     */
    protected function expect(int $tokenType): Token
    {
        $token = $this->lexer->getCachedLookaheadOrTryToGenerateLookaheadForTokenAndGetLookahead($tokenType);
        if ($token === null || $token->getType() !== $tokenType) {
            throw new Fusion\Exception('Expected token: "' . Token::typeToString($tokenType) . '"', 1635708717);
        }
        return $this->lexer->consumeLookahead();
    }

    /**
     * Checks, if the token type matches the current, if so consume it and return true.
     * @param int $tokenType
     * @return bool|null
     */
    protected function lazyExpect(int $tokenType): ?bool
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
}
