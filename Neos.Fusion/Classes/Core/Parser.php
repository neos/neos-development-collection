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
use Neos\Fusion\Core\Cache\ParserCache;
use Neos\Fusion\Core\ObjectTreeParser\Ast\FusionFile;
use Neos\Fusion\Core\ObjectTreeParser\FilePatternResolver;
use Neos\Fusion\Core\ObjectTreeParser\MergedArrayTree;
use Neos\Fusion\Core\ObjectTreeParser\MergedArrayTreeVisitor;
use Neos\Fusion\Core\ObjectTreeParser\ObjectTreeParser;
use Neos\Flow\Annotations as Flow;

/**
 * The Fusion Parser
 *
 * @api
 */
class Parser
{
    /**
     * Reserved parse tree keys for internal usage.
     */
    public static array $reservedParseTreeKeys = ['__meta', '__prototypes', '__stopInheritanceChain', '__prototypeObjectName', '__prototypeChain', '__value', '__objectType', '__eelExpression'];

    /**
     * @Flow\Inject
     * @var DslFactory
     */
    protected $dslFactory;

    /**
     * @Flow\Inject
     * @var ParserCache
     */
    protected $parserCache;

    /**
     * Parses the given Fusion source code, resolves includes and returns a merged array tree
     * as the result.
     *
     * @param string|FusionCodeCollection $sourceCode string is DEPRECATED - The Fusion source code to parse
     * @param string|null $contextPathAndFilename DEPRECATED - An optional path and filename used for relative Fusion file includes
     * @param array $mergedArrayTreeUntilNow Used internally for keeping track of the built merged array tree
     * @return array The merged array tree for the Fusion runtime, generated from the source code
     * @throws Fusion\Exception
     * @api
     */
    public function parse(string|FusionCodeCollection $sourceCode, ?string $contextPathAndFilename = null, array $mergedArrayTreeUntilNow = []): array
    {
        if ($sourceCode instanceof FusionCodeCollection) {
            $fusionCodeCollection = $sourceCode;
        } else {
            // legacy handling
            $fusionCodeCollection = new FusionCodeCollection(
                $contextPathAndFilename === null
                    ? FusionCode::fromString($sourceCode)
                    : FusionCode::fromDangerousPotentiallyDifferingSourceCodeAndContextPath($sourceCode, $contextPathAndFilename)
            );
        }

        $mergedArrayTree = new MergedArrayTree($mergedArrayTreeUntilNow);

        foreach ($fusionCodeCollection as $fusionCode) {
            $this->getMergedArrayTreeVisitor($mergedArrayTree)->visitFusionFile(
                $this->getFusionFile($fusionCode)
            );
        }

        $mergedArrayTree->buildPrototypeHierarchy();
        return $mergedArrayTree->getTree();
    }

    protected function handleFileInclude(MergedArrayTree $mergedArrayTree, string $filePattern, ?string $contextPathAndFilename): void
    {
        $filesToInclude = FilePatternResolver::resolveFilesByPattern($filePattern, $contextPathAndFilename, '.fusion');
        foreach ($filesToInclude as $file) {
            if (is_readable($file) === false) {
                throw new Fusion\Exception("Could not read file '$file' of pattern '$filePattern'.", 1347977017);
            }
            // Check if not trying to recursively include the current file via globbing
            if ($contextPathAndFilename === null
                || stat($contextPathAndFilename) !== stat($file)) {
                $fusionFile = $this->getFusionFile(FusionCode::fromFile($file));
                $this->getMergedArrayTreeVisitor($mergedArrayTree)->visitFusionFile($fusionFile);
            }
        }
    }

    protected function handleDslTranspile(string $identifier, string $code)
    {
        return $this->parserCache->cacheForDsl(
            $identifier,
            $code,
            function () use ($identifier, $code) {
                $dslObject = $this->dslFactory->create($identifier);

                $transpiledFusion = $dslObject->transpile($code);

                $fusionFile = ObjectTreeParser::parse(FusionCode::fromString('value = ' . $transpiledFusion));

                $mergedArrayTree = $this->getMergedArrayTreeVisitor(new MergedArrayTree())->visitFusionFile($fusionFile);

                $temporaryAst = $mergedArrayTree->getTree();

                $dslValue = $temporaryAst['value'];

                return $dslValue;
            }
        );
    }

    protected function getMergedArrayTreeVisitor(MergedArrayTree $mergedArrayTree): MergedArrayTreeVisitor
    {
        return new MergedArrayTreeVisitor(
            $mergedArrayTree,
            fn (...$args) => $this->handleFileInclude(...$args),
            fn (...$args) => $this->handleDslTranspile(...$args)
        );
    }

    protected function getFusionFile(FusionCode $fusionCode): FusionFile
    {
        return $this->parserCache->cacheForFusionFile(
            $fusionCode->getContextPathAndFilename(),
            fn () => ObjectTreeParser::parse($fusionCode)
        );
    }
}
