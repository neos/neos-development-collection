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
     * @param FusionSourceCodeCollection $sourceCode The Fusion source code to parse
     * @return FusionConfiguration The fusion configuration for the Fusion runtime
     * @throws Fusion\Exception
     * @api
     */
    public function parseFromSource(FusionSourceCodeCollection $sourceCode): FusionConfiguration
    {
        $mergedArrayTree = new MergedArrayTree([]);

        foreach ($sourceCode as $fusionSourceCode) {
            $this->getMergedArrayTreeVisitor($mergedArrayTree)->visitFusionFile(
                $this->parseToFusionFile($fusionSourceCode)
            );
        }

        $mergedArrayTree->buildPrototypeHierarchy();
        return FusionConfiguration::fromArray($mergedArrayTree->getTree());
    }

    /**
     * Parses the given Fusion source code, resolves includes and returns a merged array tree
     * as the result.
     *
     * @param string $sourceCode The Fusion source code to parse
     * @param string|null $contextPathAndFilename An optional path and filename used for relative Fusion file includes
     * @param array $mergedArrayTreeUntilNow Used internally for keeping track of the built merged array tree
     * @return array The merged array tree for the Fusion runtime, generated from the source code
     * @throws Fusion\Exception
     * @deprecated with Neos 8.3 – will be removed with Neos 9.0, use {@link parseFromSource} instead
     * @api
     */
    public function parse(string $sourceCode, ?string $contextPathAndFilename = null, array $mergedArrayTreeUntilNow = []): array
    {
        // legacy mapping
        $fusionSourceCode = $contextPathAndFilename === null
            ? FusionSourceCode::fromString($sourceCode)
            : FusionSourceCode::fromDangerousPotentiallyDifferingSourceCodeAndContextPath($sourceCode, $contextPathAndFilename);

        $mergedArrayTree = new MergedArrayTree($mergedArrayTreeUntilNow);

        $this->getMergedArrayTreeVisitor($mergedArrayTree)->visitFusionFile(
            $this->parseToFusionFile($fusionSourceCode)
        );

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
                $fusionFile = $this->parseToFusionFile(FusionSourceCode::fromFilePath($file));
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

                $fusionFile = ObjectTreeParser::parse(FusionSourceCode::fromString('value = ' . $transpiledFusion));

                $mergedArrayTree = $this->getMergedArrayTreeVisitor(new MergedArrayTree([]))->visitFusionFile($fusionFile);

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

    protected function parseToFusionFile(FusionSourceCode $fusionCode): FusionFile
    {
        return $this->parserCache->cacheForFusionFile(
            $fusionCode->getFilePath(),
            fn () => ObjectTreeParser::parse($fusionCode)
        );
    }
}
