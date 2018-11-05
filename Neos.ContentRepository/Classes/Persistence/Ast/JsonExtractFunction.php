<?php
declare(strict_types=1);

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonExtractFunction extends FunctionNode
{
    /**
     * @var Node
     */
    public $jsonData;

    /**
     * @var Node[]
     */
    public $jsonPaths = [];

    /**
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws DBALException
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $dbPlatform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$dbPlatform instanceof PostgreSqlPlatform && !$dbPlatform instanceof MySqlPlatform && !$dbPlatform instanceof SqlitePlatform) {
            throw DBALException::notSupported('JSON_EXTRACT');
        }

        $jsonData = $sqlWalker->walkStringPrimary($this->jsonData);
        $jsonPaths = [];

        foreach ($this->jsonPaths as $path) {
            if ($dbPlatform instanceof MySqlPlatform || $dbPlatform instanceof SqlitePlatform) {
                $jsonPaths[] = $sqlWalker->walkStringPrimary($path);
            } elseif ($dbPlatform instanceof PostgreSqlPlatform) {
                $jsonPaths[] = $path->dispatch($sqlWalker);
            }
        }

        if ($dbPlatform instanceof MySqlPlatform || $dbPlatform instanceof SqlitePlatform) {
            return sprintf('json_extract(%s, %s)', $jsonData, join(', ', $jsonPaths));
        } elseif ($dbPlatform instanceof PostgreSqlPlatform) {
            return sprintf('%s->>%s', $jsonData, join(', ', $jsonPaths));
        }
    }

    /**
     * @param Parser $parser
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->jsonData = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->jsonPaths[] = $parser->StringPrimary();
        while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->jsonPaths[] = $parser->StringPrimary();
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
