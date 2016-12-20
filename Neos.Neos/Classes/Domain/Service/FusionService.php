<?php
namespace Neos\Neos\Domain\Service;

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
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Files;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;

/**
 * The Fusion Service
 *
 * @Flow\Scope("prototype")
 * @api
 */
class FusionService
{
    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Pattern used for determining the Fusion root file for a site
     *
     * @var string
     */
    protected $siteRootFusionPattern = 'resource://%s/Private/Fusion/Root.fusion';

    /**
     * Legacy pattern used for determining the Fusion root file for a site
     *
     * @var string
     * @deprecated since 3.0 will be removed in 4.0
     */
    protected $legacySiteRootTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

    /**
     * Pattern used for determining the Fusion root file for autoIncludes
     *
     * @var string
     */
    protected $autoIncludeFusionPattern = 'resource://%s/Private/Fusion/Root.fusion';

    /**
     * Legacy pattern used for determining the TypoScript root file for autoIncludes
     *
     * @var string
     * @deprecated since 3.0 will be removed in 4.0
     */
    protected $legacyAutoIncludeTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

    /**
     * Array of Fusion files to include before the site Fusion
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/Fusion/Root.fusion',
     *         'resources://SomeVendor.OtherPackage/Private/Fusion/Root.fusion'
     *     )
     *
     * @var array
     */
    protected $prependFusionIncludes = array();

    /**
     * Array of Fusion files to include after the site Fusion
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/Fusion/Root.fusion',
     *         'resources://SomeVendor.OtherPackage/Private/Fusion/Root.fusion'
     *     )
     *
     * @var array
     */
    protected $appendFusionIncludes = array();

    /**
     * @Flow\InjectConfiguration("fusion.autoInclude")
     * @var array
     */
    protected $autoIncludeConfiguration = array();

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * Initializes the parser
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->fusionParser->setObjectTypeNamespace('default', 'Neos.Neos');
    }

    /**
     * Create a runtime for the given site node
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $currentSiteNode
     * @param ControllerContext $controllerContext
     * @return Runtime
     */
    public function createRuntime(NodeInterface $currentSiteNode, ControllerContext $controllerContext)
    {
        $fusionObjectTree = $this->getMergedFusionObjectTree($currentSiteNode);
        $fusionRuntime = new Runtime($fusionObjectTree, $controllerContext);
        return $fusionRuntime;
    }

    /**
     * Returns a merged Fusion object tree in the context of the given nodes
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $startNode Node marking the starting point
     * @return array The merged object tree as of the given node
     * @throws \Neos\Neos\Domain\Exception
     */
    public function getMergedFusionObjectTree(NodeInterface $startNode)
    {
        $contentContext = $startNode->getContext();
        $siteResourcesPackageKey = $contentContext->getCurrentSite()->getSiteResourcesPackageKey();

        $siteRootFusionPathAndFilename = sprintf($this->siteRootFusionPattern, $siteResourcesPackageKey);
        $siteRootFusionCode = $this->readExternalFusionFile($siteRootFusionPathAndFilename);

        if ($siteRootFusionCode === '') {
            $siteRootFusionPathAndFilename = sprintf($this->legacySiteRootTypoScriptPattern, $siteResourcesPackageKey);
            $siteRootFusionCode = $this->readExternalFusionFile($siteRootFusionPathAndFilename);
        }

        $mergedFusionCode = '';
        $mergedFusionCode .= $this->generateNodeTypeDefinitions();
        $mergedFusionCode .= $this->getFusionIncludes($this->prepareAutoIncludeFusion());
        $mergedFusionCode .= $this->getFusionIncludes($this->prependFusionIncludes);
        $mergedFusionCode .= $siteRootFusionCode;
        $mergedFusionCode .= $this->getFusionIncludes($this->appendFusionIncludes);

        return $this->fusionParser->parse($mergedFusionCode, $siteRootFusionPathAndFilename);
    }

    /**
     * Reads the Fusion file from the given path and filename.
     * If it doesn't exist, this function will just return an empty string.
     *
     * @param string $pathAndFilename Path and filename of the Fusion file
     * @return string The content of the .fusion file, plus one chr(10) at the end
     */
    protected function readExternalFusionFile($pathAndFilename)
    {
        return (is_file($pathAndFilename)) ? Files::getFileContents($pathAndFilename) . chr(10) : '';
    }

    /**
     * Generate Fusion prototype definitions for all node types
     *
     * Only fully qualified node types (e.g. MyVendor.MyPackage:NodeType) will be considered.
     *
     * @return string
     */
    protected function generateNodeTypeDefinitions()
    {
        $code = '';
        /** @var NodeType $nodeType */
        foreach ($this->nodeTypeManager->getNodeTypes(false) as $nodeType) {
            $code .= $this->generateFusionForNodeType($nodeType);
        }
        return $code;
    }

    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * A prototype will be rendererd with the generator-class defined in the
     * nodeType-configuration 'fusion.prototypeGenerator'
     *
     * @param NodeType $nodeType
     * @return string
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function generateFusionForNodeType(NodeType $nodeType)
    {
        if ($nodeType->hasConfiguration('options.fusion.prototypeGenerator') && $nodeType->getConfiguration('options.fusion.prototypeGenerator') !== null) {
            $generatorClassName = $nodeType->getConfiguration('options.fusion.prototypeGenerator');
            if (!class_exists($generatorClassName)) {
                throw new \Neos\Neos\Domain\Exception('Fusion prototype-generator Class ' . $generatorClassName . ' does not exist');
            }
            $generator = $this->objectManager->get($generatorClassName);
            if (!$generator instanceof DefaultPrototypeGeneratorInterface) {
                throw new \Neos\Neos\Domain\Exception('Fusion prototype-generator Class ' . $generatorClassName . ' does not implement interface ' . DefaultPrototypeGeneratorInterface::class);
            }
            return $generator->generate($nodeType);
        }
        return '';
    }

    /**
     * Concatenate the given Fusion resources with include statements
     *
     * @param array $fusionResources An array of Fusion resource URIs
     * @return string A string of include statements for all resources
     */
    protected function getFusionIncludes(array $fusionResources)
    {
        $code = chr(10);
        foreach ($fusionResources as $fusionResource) {
            $code .= 'include: ' . (string)$fusionResource . chr(10);
        }
        $code .= chr(10);
        return $code;
    }

    /**
     * Prepares an array with Fusion paths to auto include before the Site Fusion.
     *
     * @return array
     */
    protected function prepareAutoIncludeFusion()
    {
        $autoIncludeFusion = array();
        foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
            if (isset($this->autoIncludeConfiguration[$packageKey]) && $this->autoIncludeConfiguration[$packageKey] === true) {
                $autoIncludeFusionFile = sprintf($this->autoIncludeFusionPattern, $packageKey);
                if (is_file($autoIncludeFusionFile)) {
                    $autoIncludeFusion[] = $autoIncludeFusionFile;
                } else {
                    // If there is no Root.fusion found in the default path pattern or the legacy path pattern
                    // use the default path pattern so an exception will show the correct path pattern and not a
                    // legacy path pattern
                    $legacyAutoIncludeFusionFile = sprintf($this->legacyAutoIncludeTypoScriptPattern, $packageKey);
                    if (is_file($legacyAutoIncludeFusionFile)) {
                        $autoIncludeFusion[] = $legacyAutoIncludeFusionFile;
                    } else {
                        $autoIncludeFusion[] = $autoIncludeFusionFile;
                    }
                }
            }
        }

        return $autoIncludeFusion;
    }

    /**
     * Set the pattern for including the site root Fusion
     *
     * @param string $siteRootFusionPattern A string for the sprintf format that takes the site package key as a single placeholder
     * @return void
     */
    public function setSiteRootFusionPattern($siteRootFusionPattern)
    {
        $this->siteRootFusionPattern = $siteRootFusionPattern;
    }

    /**
     * Get the Fusion resources that are included before the site Fusion.
     *
     * @return array
     */
    public function getPrependFusionIncludes()
    {
        return $this->prependFusionIncludes;
    }

    /**
     * Set Fusion resources that should be prepended before the site Fusion,
     * it defaults to the Neos Root.fusion Fusion.
     *
     * @param array $prependFusionIncludes
     * @return void
     */
    public function setPrependFusionIncludes(array $prependFusionIncludes)
    {
        $this->prependFusionIncludes = $prependFusionIncludes;
    }

    /**
     * Get Fusion resources that will be appended after the site Fusion.
     *
     * @return array
     */
    public function getAppendFusionIncludes()
    {
        return $this->appendFusionIncludes;
    }

    /**
     * Set Fusion resources that should be appended after the site Fusion,
     * this defaults to an empty array.
     *
     * @param array $appendFusionIncludes An array of Fusion resource URIs
     * @return void
     */
    public function setAppendFusionIncludes(array $appendFusionIncludes)
    {
        $this->appendFusionIncludes = $appendFusionIncludes;
    }
}
