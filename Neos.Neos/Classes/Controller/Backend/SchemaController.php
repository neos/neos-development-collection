<?php
declare(strict_types=1);

namespace Neos\Neos\Controller\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Service\NodeTypeSchemaBuilder;
use Neos\Neos\Service\VieSchemaBuilder;

/**
 * @Flow\Scope("singleton")
 */
class SchemaController extends ActionController
{
    /**
     * @var VieSchemaBuilder
     * @Flow\Inject
     */
    protected $vieSchemaBuilder;

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
     * Generate and renders the JSON schema for the node types for VIE.
     * Schema format example: http://schema.rdfs.org/all.json
     *
     * @return string
     */
    public function vieSchemaAction(): string
    {
        $version = $this->request->hasArgument('version') ? $this->request->getArgument('version') : '';
        $cacheIdentifier = 'vieSchema_' . $version;

        $this->response->setContentType('application/json');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Cache-Control', 'max-age=' . (3600 * 24 * 7));

        $vieSchema = $this->nodeTypeSchemaCache->get($cacheIdentifier);
        if (!$vieSchema) {
            $vieSchema = json_encode($this->vieSchemaBuilder->generateVieSchema());
            $this->nodeTypeSchemaCache->flushByTag('vie');
            $this->nodeTypeSchemaCache->set($cacheIdentifier, $vieSchema, ['vie']);
        }
        return $vieSchema;
    }

    /**
     * Get the node type configuration schema for the Neos UI
     *
     * @return string
     */
    public function nodeTypeSchemaAction(): string
    {
        $version = $this->request->hasArgument('version') ? $this->request->getArgument('version') : '';
        $cacheIdentifier = 'nodeTypeSchema_' . $version;

        $this->response->setContentType('application/json');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Cache-Control', 'max-age=' . (3600 * 24 * 7));

        $nodeTypeSchema = $this->nodeTypeSchemaCache->get($cacheIdentifier);
        if (!$nodeTypeSchema) {
            $nodeTypeSchema = json_encode($this->nodeTypeSchemaBuilder->generateNodeTypeSchema());
            $this->nodeTypeSchemaCache->flushByTag('nodeType');
            $this->nodeTypeSchemaCache->set($cacheIdentifier, $nodeTypeSchema, ['nodeType']);
        }
        return $nodeTypeSchema;
    }
}
