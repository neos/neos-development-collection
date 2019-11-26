<?php
declare(strict_types=1);

namespace Neos\Fusion\Afx\Parser;

/*
 * This file is part of the Neos.Fusion.Afx package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Class Parser
 * @package Neos\Fusion\Afx\Parser
 */
class Parser
{
    /**
     * @var Lexer
     */
    protected $lexer;

    /**
     * Parser constructor.
     * @param $string
     */
    public function __construct($string)
    {
        $this->lexer = new Lexer($string);
    }

    /**
     * @return array
     * @throws AfxParserException
     */
    public function parse(): array
    {
        return Expression\NodeList::parse($this->lexer);
    }
}
