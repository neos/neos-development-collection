<?php
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
use Neos\Fusion\Exception;

/**
 * Contract for a Fusion parser
 *
 * @api
 */
interface ParserInterface
{
    /**
     * Parses the given Fusion source code and returns an object tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further Fusion files
     * @param array $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @return array A Fusion object tree, generated from the source code
     * @throws Exception
     * @api
     */
    public function parse($sourceCode, $contextPathAndFilename = null, array $objectTreeUntilNow = []);
}
