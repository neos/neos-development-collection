<?php
namespace TYPO3\TYPO3CR\Persistence\Ast;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Custom DQL function to replace a value in a string
 *
 * "REPLACE" "(" String "," Search "," Replace ")"
 */
class ReplaceFunction extends FunctionNode
{
    /**
     * @var InputParameter
     */
    public $string;

    /**
     * @var InputParameter
     */
    public $search;

    /**
     * @var InputParameter
     */
    public $replace;

    /**
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'REPLACE(' . $this->string->dispatch($sqlWalker) . ','
        . $this->search->dispatch($sqlWalker) . ','
        . $this->replace->dispatch($sqlWalker) . ')';
    }

    /**
     * @param \Doctrine\ORM\Query\Parser $parser
     *
     * @return void
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->search = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->replace = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
