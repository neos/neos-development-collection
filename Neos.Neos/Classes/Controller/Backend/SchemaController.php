<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Backend;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Service\NodeTypeSchemaBuilder;

#[Flow\Scope('singleton')]
class SchemaController extends ActionController
{
    /**
     * @var NodeTypeSchemaBuilder
     * @Flow\Inject
     */
    protected $nodeTypeSchemaBuilder;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $nodeTypeSchemaCache;

    /**
     * Get the node type configuration schema for the Neos UI
     *
     * @return string
     */
    public function nodeTypeSchemaAction(): string
    {
        if ($this->request->hasArgument('version')) {
            /** @var string $version */
            $version = $this->request->getArgument('version');
        } else {
            $version = '';
        }
        $cacheIdentifier = 'nodeTypeSchema_' . $version;

        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Cache-Control', 'max-age=' . (3600 * 24 * 7));

        $nodeTypeSchema = $this->nodeTypeSchemaCache->get($cacheIdentifier);
        if (!$nodeTypeSchema) {
            $nodeTypeSchema = json_encode($this->nodeTypeSchemaBuilder->generateNodeTypeSchema());
            $this->nodeTypeSchemaCache->flushByTag('nodeType');
            $this->nodeTypeSchemaCache->set($cacheIdentifier, $nodeTypeSchema, ['nodeType']);
        }
        return $nodeTypeSchema;
    }
}
