<?php

declare(strict_types=1);

namespace Neos\Fusion\Migrations;

use Neos\Flow\Core\Migrations\AbstractMigration;
use Neos\Fusion\Migrations\EelExpression\EelExpressionFusionPath;
use Neos\Fusion\Migrations\EelExpression\EelExpressionTransformer;
use Neos\Fusion\Migrations\EelExpression\RegexCommentTemplatePair;
use Neos\Fusion\Migrations\FusionPrototype\FusionPrototypeNameAddComment;
use Neos\Fusion\Migrations\FusionPrototype\FusionPrototypeNameReplacement;
use Neos\Fusion\Migrations\FusionPrototype\FusionPrototypeTransformer;
use Neos\Utility\Files;

trait FusionMigrationTrait
{
    /**
     * @var array<string,string>
     */
    private array $eelReplacementOperations = [];

    /**
     * @var array<string,array<string,string>>
     */
    private array $eelReplacementOperationsPerFusionPath = [];

    /**
     * @var array<RegexCommentTemplatePair>
     */
    private array $regexConditionalCommentsOperations = [];

    /**
     * @var array<FusionPrototypeNameReplacement>
     */
    private array $fusionPrototypeNameReplacements = [];

    /**
     * @var array<FusionPrototypeNameAddComment>
     */
    private array $fusionPrototypeNameAddComments = [];

    final public function replaceEelExpression(string $pregSearch, string $pregReplace): void
    {
        $this->eelReplacementOperations[$pregSearch] = $pregReplace;
    }

    final public function renameFusionPrototype(string $oldName, string $newName, string $comment = ''): void
    {
        $this->fusionPrototypeNameReplacements[$oldName] = new FusionPrototypeNameReplacement($oldName, $newName, $comment);
    }

    final public function addCommentToFusionPrototype(string $name, string $comment): void
    {
        $this->fusionPrototypeNameAddComments[$name] = new FusionPrototypeNameAddComment($name, $comment);
    }

    /**
     * @internal experimental api, keeps prototype() extensions intact
     */
    final public function renameOnlyFusionPrototypeInstantiations(string $oldName, string $newName, string $comment = ''): void
    {
        $this->fusionPrototypeNameReplacements[$oldName] = new FusionPrototypeNameReplacement($oldName, $newName, $comment, skipPrototypeDefinitions: true);
    }

    /**
     * @internal experimental api, requires to specify $fusionPath as parsed segments separated by slashes similar to the internal runtime path format
     */
    final public function replaceEelExpressionInsideFusionPath(string $pregSearch, string $pregReplace, string $fusionPath): void
    {
        $this->eelReplacementOperationsPerFusionPath[$fusionPath][$pregSearch] = $pregReplace;
    }

    /**
     * @internal experimental api, allows to specify a comment with a place-holder like %LINE - this might not be the correct line after applying two migration to a file
     */
    final public function addCommentsIfRegexMatches(string $regex, string $comment): void
    {
        $this->regexConditionalCommentsOperations[$regex] = new RegexCommentTemplatePair($regex, $comment);
    }

    final public function applyEelFusionOperations(): void
    {
        $filePaths = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->targetPackageData['path']),
            ),
            '/\.fusion$/',
            \RecursiveRegexIterator::GET_MATCH,
        );

        $pregSearches = array_keys($this->eelReplacementOperations);
        $pregReplacements = array_values($this->eelReplacementOperations);

        foreach ($filePaths as $filePath => $_) {
            $originalContents = file_get_contents($filePath);

            $commentPrefix = sprintf('TODO %s ', $this->getIdentifier());
            $onWarning = fn (string $comment) => $this->showWarning(sprintf('File %s: %s', Files::getRelativePath(
                $this->targetPackageData['path'],
                $filePath
            ), $comment));

            $eelTransformer = EelExpressionTransformer::forContent($originalContents, $commentPrefix, $onWarning);
            $eelTransformer = $eelTransformer->process(function (string $expression, EelExpressionFusionPath $currentFusionPath) use ($pregSearches, $pregReplacements) {
                foreach ($this->eelReplacementOperationsPerFusionPath as $fusionPath => $operations) {
                    if ($currentFusionPath->contains($fusionPath)) {
                        $pregSearches = array_merge($pregSearches, array_keys($operations));
                        $pregReplacements = array_merge($pregReplacements, array_values($operations));
                    }
                }

                $newExpression = preg_replace($pregSearches, $pregReplacements, $expression);
                if ($newExpression === null) {
                    throw new \RuntimeException(sprintf('Malformed preg replacement for expression %s', $expression), 1759641629);
                }
                return $newExpression;
            });

            $eelTransformer = $eelTransformer->addCommentsIfRegexesMatch($this->regexConditionalCommentsOperations);

            $newContents = $eelTransformer->getProcessedContent();

            $fusionPrototypeTransformer = FusionPrototypeTransformer::forContent($newContents, $commentPrefix, $onWarning);

            if ($this->fusionPrototypeNameReplacements !== []) {
                $fusionPrototypeTransformer = $fusionPrototypeTransformer->processFusionPrototypeNameReplacements(
                    ...array_values($this->fusionPrototypeNameReplacements)
                );
            }

            if ($this->fusionPrototypeNameAddComments !== []) {
                $fusionPrototypeTransformer = $fusionPrototypeTransformer->processFusionPrototypeNameAddComments(
                    ...array_values($this->fusionPrototypeNameAddComments)
                );
            }

            $newContents = $fusionPrototypeTransformer->getProcessedContent();

            if ($originalContents !== $newContents) {
                file_put_contents($filePath, $newContents);
            }
        }
    }

    public function execute()
    {
        /** Que into flow's {@see AbstractMigration::execute()} - there is no other extension point */
        parent::execute();
        $this->applyEelFusionOperations();
    }
}
