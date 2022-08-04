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
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Service\NodeTypeSchemaBuilder;
use Neos\Neos\Service\NodeTypeSchemaBuilderFactory;

#[Flow\Scope('singleton')]
class SchemaController extends ActionController
{
    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $nodeTypeSchemaCache;

    /**
     * @var ContentRepositoryRegistry
     * @Flow\Inject
     */
    protected $contentRepositoryRegistry;

    /**
     * Get the node type configuration schema for the Neos UI
     *
     * @return string
     */
    public function nodeTypeSchemaAction(): string
    {
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryIdentifier;

        if ($this->request->hasArgument('version')) {
            /** @var string $version */
            $version = $this->request->getArgument('version');
        } else {
            $version = '';
        }
        $cacheIdentifier = $contentRepositoryIdentifier->value . '_nodeTypeSchema_' . $version;

        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Cache-Control', 'max-age=' . (3600 * 24 * 7));

        $nodeTypeSchema = $this->nodeTypeSchemaCache->get($cacheIdentifier);
        if (!$nodeTypeSchema) {
            $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
            $nodeTypeSchemaBuilder = NodeTypeSchemaBuilder::create($contentRepository->getNodeTypeManager());
            $nodeTypeSchema = json_encode($nodeTypeSchemaBuilder->generateNodeTypeSchema());
            $this->nodeTypeSchemaCache->flushByTag('nodeType');
            $this->nodeTypeSchemaCache->set($cacheIdentifier, $nodeTypeSchema, ['nodeType']);
        }
        return $nodeTypeSchema;
    }
}
