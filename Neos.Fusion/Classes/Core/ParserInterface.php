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

/**
 * Contract for a Fusion parser
 *
 * @api
 */
interface ParserInterface
{
    /**
     * Parses the given Fusion source code, resolves includes and returns a merged array tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string|null $contextPathAndFilename An optional path and filename used for relative Fusion file includes
     * @param array $mergedArrayTreeUntilNow Used internally for keeping track of the built merged array tree
     * @return array The merged array tree for the Fusion runtime, generated from the source code
     * @throws Fusion\Exception
     * @api
     */
    public function parse(string $sourceCode, ?string $contextPathAndFilename = null, array $mergedArrayTreeUntilNow = []): array;
}
