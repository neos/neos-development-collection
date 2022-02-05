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
use Neos\Fusion\Core\ObjectTreeParser\FilePatternResolver;
use Neos\Fusion\Core\ObjectTreeParser\Lexer;
use Neos\Fusion\Core\ObjectTreeParser\ObjectTree;
use Neos\Fusion\Core\ObjectTreeParser\ObjectTreeAstVisitor;
use Neos\Fusion\Core\ObjectTreeParser\PredictiveParser;
use Neos\Flow\Annotations as Flow;

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
     * @Flow\Inject
     * @var DslFactory
     */
    protected $dslFactory;

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
        $lexer = new Lexer($sourceCode);
        $fusionFileAst = (new PredictiveParser($lexer, $contextPathAndFilename))->parse();

        $objectTree = new ObjectTree();
        $objectTree->setObjectTree($objectTreeUntilNow);

        $objectTreeBuilder = new ObjectTreeAstVisitor($objectTree, [$this, 'handleFileInclude'], [$this, 'handleDslTranspile']);
        $objectTree = $objectTreeBuilder->visitFusionFileAst($fusionFileAst);

        $objectTree->buildPrototypeHierarchy();
        return $objectTree->getObjectTree();
    }

    /**
     * @internal Exposed to be testable and easily transformable to closure.
     */
    public function handleFileInclude(ObjectTree $objectTree, string $filePattern, ?string $contextPathAndFilename): ObjectTree
    {
        $filesToInclude = FilePatternResolver::resolveFilesByPattern($filePattern, $contextPathAndFilename, '.fusion');
        foreach ($filesToInclude as $file) {
            if (is_readable($file) === false) {
                throw new Fusion\Exception("Could not read file '$file' of pattern '$filePattern'.", 1347977017);
            }
            // Check if not trying to recursively include the current file via globbing
            if ($contextPathAndFilename === null
                || stat($contextPathAndFilename) !== stat($file)) {

                $lexer = new Lexer(file_get_contents($file));

                $fusionFileAst = (new PredictiveParser($lexer, $file))->parse();

                (new ObjectTreeAstVisitor($objectTree, [$this, 'handleFileInclude'], [$this, 'handleDslTranspile']))->visitFusionFileAst($fusionFileAst);
            }
        }
        return $objectTree;
    }

    /**
     * @internal Exposed to be testable and easily transformable to closure.
     */
    public function handleDslTranspile(string $identifier, string $code)
    {
        $dslObject = $this->dslFactory->create($identifier);

        try {
            $transpiledFusion = $dslObject->transpile($code);
        } catch (\Exception $e) {
            // TODO
            // convert all exceptions from dsl transpilation to fusion exception and add file and line info
            throw $e;
        }

        $lexer = new Lexer('value = ' . $transpiledFusion);
        $parser = new PredictiveParser($lexer);
        $fusionFileAst = $parser->parse();

        $objectTree = (new ObjectTreeAstVisitor(new ObjectTree(), [$this, 'handleFileInclude'], [$this, 'handleDslTranspile']))->visitFusionFileAst($fusionFileAst);

        $temporaryAst = $objectTree->getObjectTree();
        return $temporaryAst['value'];
    }
}
