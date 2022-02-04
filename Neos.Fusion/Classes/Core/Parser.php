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
use Neos\Fusion\Core\ObjectTreeParser\Lexer;
use Neos\Fusion\Core\ObjectTreeParser\ObjectTree;
use Neos\Fusion\Core\ObjectTreeParser\PredictiveParser;

/**
 * The Fusion Parser
 *
 * @api
 */
class Parser implements ParserInterface
{
    /**
     * Reserved parse tree keys for internal usage.
     *
     * @deprecated use ParserInterface::RESERVED_PARSE_TREE_KEYS
     * @var array
     */
    public static $reservedParseTreeKeys = ParserInterface::RESERVED_PARSE_TREE_KEYS;

    /**
     * Parses the given Fusion source code and returns an object tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string|null $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further Fusion files
     * @param array $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @return array A Fusion object tree, generated from the source code
     * @throws Fusion\Exception
     * @api
     */
    public function parse(string $sourceCode, ?string $contextPathAndFilename = null, array $objectTreeUntilNow = []): array
    {
        $objectTree = new ObjectTree();
        $objectTree->setObjectTree($objectTreeUntilNow);

        $lexer = new Lexer($sourceCode);

        PredictiveParser::parse($objectTree, $lexer, $contextPathAndFilename);

        $objectTree->buildPrototypeHierarchy();
        return $objectTree->getObjectTree();
    }
}
