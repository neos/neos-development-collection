<?php
namespace TYPO3\TypoScript\Core;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\TypoScript\Exception;

/**
 * Contract for a TypoScript parser
 *
 * @api
 */
interface ParserInterface
{
    /**
     * Parses the given TypoScript source code and returns an object tree
     * as the result.
     *
     * @param string $sourceCode The TypoScript source code to parse
     * @param string $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further TypoScript files
     * @param array $objectTreeUntilNow Used internally for keeping track of the built object tree
     * @return array A TypoScript object tree, generated from the source code
     * @throws Exception
     * @api
     */
    public function parse($sourceCode, $contextPathAndFilename = null, array $objectTreeUntilNow = array());

    /**
     * Sets the given alias to the specified namespace.
     *
     * The namespaces defined through this setter or through a "namespace" declaration
     * in one of the TypoScripts are used to resolve a fully qualified TypoScript
     * object name while parsing TypoScript code.
     *
     * The alias is the handle by wich the namespace can be referred to.
     * The namespace is, by convention, a package key which must correspond to a
     * namespace used in the prototype definitions for TypoScript object types.
     *
     * The special alias "default" is used as a fallback for resolution of unqualified
     * TypoScript object types.
     *
     * @param string $alias An alias for the given namespace, for example "neos"
     * @param string $namespace The namespace, for example "TYPO3.Neos"
     * @return void
     * @api
     */
    public function setObjectTypeNamespace($alias, $namespace);
}
