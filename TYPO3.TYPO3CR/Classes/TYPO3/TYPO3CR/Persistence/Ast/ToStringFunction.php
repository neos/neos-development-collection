<?php
namespace Neos\ContentRepository\Persistence\Ast;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Custom DQL function to explicitly cast a value to a string
 */
class ToStringFunction extends FunctionNode
{

    /**
     * @var mixed
     */
    protected $stringPrimary;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        // This type is defined by ANSI SQL
        $targetType = 'VARCHAR';
        // MySQL needs a CHAR type for string conversion (http://dev.mysql.com/doc/refman/5.7/en/type-conversion.html)
        if ($sqlWalker->getConnection()->getDatabasePlatform()->getName() === 'mysql') {
            $targetType = 'CHAR';
        }
        return 'CAST(' . $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary) . ' AS ' . $targetType . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
