<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class Lexer
{
    // Difference to: Neos\Eel\Package::EelExpressionRecognizer
    // added an atomic group (to prevent catastrophic backtracking) and removed the end anchor $
    protected const PATTERN_EEL_EXPRESSION = <<<'REGEX'
    /
      ^\${(?P<exp>
        (?>
          { (?P>exp) }          # match object literal expression recursively
          |[^{}"']+	            # simple eel expression without quoted strings
          |"[^"\\]*			    # double quoted strings with possibly escaped double quotes
            (?:
              \\.			# escaped character (quote)
              [^"\\]*		# unrolled loop following Jeffrey E.F. Friedl
            )*"
          |'[^'\\]*			# single quoted strings with possibly escaped single quotes
            (?:
              \\.			# escaped character (quote)
              [^'\\]*		# unrolled loop following Jeffrey E.F. Friedl
            )*'
        )*
      )}
    /x
    REGEX;

    protected const TOKEN_REGEX = [
        Token::SLASH_COMMENT => '/^\\/\\/.*/',
        Token::HASH_COMMENT => '/^#.*/',
        Token::MULTILINE_COMMENT => <<<'REGEX'
        `^
          /\*               # start of a comment '/*'
          [^*]*             # match everything until special case '*'
          (?:
            \*[^/]          # if after the '*' there is a '/' break, else continue
            [^*]*           # until the special case '*' is encountered - unrolled loop following Jeffrey Friedl
          )*
          \*/               # the end of a comment.
        `x
        REGEX,

        Token::NEWLINE => '/^[\n\r]+/',
        Token::SPACE => '/^[ \t]+/',

        // VALUE ASSIGNMENT
        Token::TRUE_VALUE => '/^(?>true|TRUE)\\b/',
        Token::FALSE_VALUE => '/^(?>false|FALSE)\\b/',
        Token::NULL_VALUE => '/^(?>null|NULL)\\b/',
        Token::INTEGER => '/^-?[0-9]+/',
        Token::FLOAT => '/^-?[0-9]+\.[0-9]+/',

        Token::DSL_EXPRESSION_START => '/^[a-zA-Z0-9\.]++(?=`)/',
        Token::DSL_EXPRESSION_CONTENT => '/^`[^`]*+`/',
        Token::EEL_EXPRESSION => self::PATTERN_EEL_EXPRESSION,

        // Object type part
        Token::FUSION_OBJECT_NAME => '/^[0-9a-zA-Z.]+:[0-9a-zA-Z.]+/',

        // Keywords
        Token::INCLUDE => '/^include\\s*:/',

        // Object path segments
        Token::PROTOTYPE_START => '/^prototype\(/',
        Token::META_PATH_START => '/^@/',
        Token::OBJECT_PATH_PART => '/^[a-zA-Z0-9_:-]+/',

        // Operators
        Token::ASSIGNMENT => '/^=/',
        Token::COPY => '/^</',
        Token::UNSET => '/^>/',

        // Symbols
        Token::DOT => '/^\./',
        Token::COLON => '/^:/',
        Token::RPAREN => '/^\)/',
        Token::LBRACE => '/^{/',
        Token::RBRACE => '/^}/',

        // Strings
        Token::STRING_DOUBLE_QUOTED => <<<'REGEX'
        /^
          "[^"\\]*              # double quoted strings with possibly escaped double quotes
            (?:
              \\.               # escaped character (quote)
              [^"\\]*           # unrolled loop following Jeffrey E.F. Friedl
            )*
          "
        /x
        REGEX,
        Token::STRING_SINGLE_QUOTED => <<<'REGEX'
        /^
          '[^'\\]*              # single quoted strings with possibly escaped single quotes
            (?:
              \\.               # escaped character (quote)
              [^'\\]*           # unrolled loop following Jeffrey E.F. Friedl
            )*
          '
        /x
        REGEX,

        Token::FILE_PATTERN => '`^[a-zA-Z0-9.*:/_-]+`',
    ];

    protected string $code = '';

    protected int $codeLen = 0;

    protected int $cursor = 0;

    protected ?Token $lookahead = null;

    public function __construct(string $code)
    {
        $code = str_replace(["\r\n", "\r"], "\n", $code);
        $this->code = $code;
        $this->codeLen = strlen($code);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }

    public function consumeLookahead(): Token
    {
        $token = $this->lookahead;
        $this->lookahead = null;
        return $token;
    }

    public function getCachedLookaheadOrTryToGenerateLookaheadForTokenAndGetLookahead(int $tokenType): ?Token
    {
        if ($this->lookahead !== null) {
            return $this->lookahead;
        }
        if ($this->cursor === $this->codeLen) {
            return $this->lookahead = new Token(Token::EOF, '');
        }
        if ($tokenType === Token::EOF) {
            return null;
        }

        $regexForToken = self::TOKEN_REGEX[$tokenType];

        $remainingCode = substr($this->code, $this->cursor);

        if (preg_match($regexForToken, $remainingCode, $matches) !== 1) {
            return null;
        }

        $this->cursor += \strlen($matches[0]);

        return $this->lookahead = new Token($tokenType, $matches[0]);
    }
}
