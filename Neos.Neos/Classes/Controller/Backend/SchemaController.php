<?php
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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Service\NodeTypeSchemaBuilder;
use Neos\Neos\Service\VieSchemaBuilder;

/**
 * The TYPO3 Module
 *
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
     * Generate and renders the JSON schema for the node types for VIE.
     * Schema format example: http://schema.rdfs.org/all.json
     *
     * @return string
     */
    public function vieSchemaAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        return json_encode($this->vieSchemaBuilder->generateVieSchema());
    }

    /**
     * Get the node type configuration schema for the Neos UI
     *
     * @return string
     */
    public function nodeTypeSchemaAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        return json_encode($this->nodeTypeSchemaBuilder->generateNodeTypeSchema());
    }
}
