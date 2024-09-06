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
 * Contract for a Fusion DSL parser
 *
 * A dsl can be registered like:
 *
 *     Neos:
 *       Fusion:
 *         dsl:
 *           afx: Neos\Fusion\Afx\Dsl\AfxDslImplementation
 *
 * And will be available via its identifier in Fusion:
 *
 *     root = afx`
 *         <div>Hello World</div>
 *     `
 * The returned string must be a valid fusion string,
 * which is parsed again:
 *
 *     Neos.Fusion:Tag {
 *         tagName = 'div'
 *         content = 'Hello World'
 *     }
 *
 * @api
 */
interface DslInterface
{
    /**
     * Transpile the given dsl-code to fusion-code
     *
     * @param string $code
     * @return string
     * @throws Fusion\Exception
     */
    public function transpile(string $code): string;
}
