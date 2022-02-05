<?php

namespace Neos\Fusion\Core\ObjectTreeParser;

use Neos\Fusion;

class FileIncludeHandler
{
    public function handle(ObjectTree $objectTree, string $filePattern, ?string $contextPathAndFilename): ObjectTree
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

                $fusionFileAst = (new PredictiveParser($lexer, $contextPathAndFilename))->parse();

                (new ObjectTreeAstVisitor($objectTree))->visitFusionFileAst($fusionFileAst);
            }
        }
        return $objectTree;
    }
}
