<?php

namespace Neos\Fusion\Core\ObjectTreeParser;

use Neos\Fusion\Core\DslFactory;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectDefinitionAst;
use Neos\Fusion\Core\ObjectTreeParser\Ast\ObjectPathPartAst;
use Neos\Flow\Annotations as Flow;

class DslExpressionHandler
{
    /**
     * @Flow\Inject
     * @var DslFactory
     */
    protected $dslFactory;

    public function handle(string $identifier, string $code)
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

        $objectTree = (new ObjectTreeAstVisitor(new ObjectTree()))->visitFusionFileAst($fusionFileAst);

        $temporaryAst = $objectTree->getObjectTree();
        return $temporaryAst['value'];
    }
}
