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
use TYPO3\TypoScript\Core\Parser;
use TYPO3\TypoScript\Core\Runtime;

/**
 * The TypoScript Service
 *
 * @Flow\Scope("prototype")
 * @api
 */
class TypoScriptService
{
    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $typoScriptParser;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Pattern used for determining the TypoScript root file for a site
     *
     * @var string
     */
    protected $siteRootTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

    /**
     * Pattern used for determining the TypoScript root file for a site
     *
     * @var string
     * @deprecated since 1.2 will be removed in 2.0
     */
    protected $legacySiteRootTypoScriptPattern = 'resource://%s/Private/TypoScripts/Library/Root.ts2';

    /**
     * Pattern used for determining the TypoScript root file for autoIncludes
     *
     * @var string
     */
    protected $autoIncludeTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

    /**
     * Array of TypoScript files to include before the site TypoScript
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/TypoScript/Root.ts2',
     *         'resources://SomeVendor.OtherPackage/Private/TypoScript/Root.ts2'
     *     )
     *
     * @var array
     */
    protected $prependTypoScriptIncludes = array();

    /**
     * Array of TypoScript files to include after the site TypoScript
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/TypoScript/Root.ts2',
     *         'resources://SomeVendor.OtherPackage/Private/TypoScript/Root.ts2'
     *     )
     *
     * @var array
     */
    protected $appendTypoScriptIncludes = array();

    /**
     * @Flow\InjectConfiguration("typoScript.autoInclude")
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
        $this->typoScriptParser->setObjectTypeNamespace('default', 'Neos.Neos');
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
        $typoScriptObjectTree = $this->getMergedTypoScriptObjectTree($currentSiteNode);
        $typoScriptRuntime = new Runtime($typoScriptObjectTree, $controllerContext);
        return $typoScriptRuntime;
    }

    /**
     * Returns a merged TypoScript object tree in the context of the given nodes
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $startNode Node marking the starting point
     * @return array The merged object tree as of the given node
     * @throws \Neos\Neos\Domain\Exception
     */
    public function getMergedTypoScriptObjectTree(NodeInterface $startNode)
    {
        $contentContext = $startNode->getContext();
        $siteResourcesPackageKey = $contentContext->getCurrentSite()->getSiteResourcesPackageKey();

        $siteRootTypoScriptPathAndFilename = sprintf($this->siteRootTypoScriptPattern, $siteResourcesPackageKey);
        $siteRootTypoScriptCode = $this->readExternalTypoScriptFile($siteRootTypoScriptPathAndFilename);

        if ($siteRootTypoScriptCode === '') {
            $siteRootTypoScriptPathAndFilename = sprintf($this->legacySiteRootTypoScriptPattern, $siteResourcesPackageKey);
            $siteRootTypoScriptCode = $this->readExternalTypoScriptFile($siteRootTypoScriptPathAndFilename);
        }

        $mergedTypoScriptCode = '';
        $mergedTypoScriptCode .= $this->generateNodeTypeDefinitions();
        $mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->prepareAutoIncludeTypoScript());
        $mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->prependTypoScriptIncludes);
        $mergedTypoScriptCode .= $siteRootTypoScriptCode;
        $mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->appendTypoScriptIncludes);

        return $this->typoScriptParser->parse($mergedTypoScriptCode, $siteRootTypoScriptPathAndFilename);
    }

    /**
     * Reads the TypoScript file from the given path and filename.
     * If it doesn't exist, this function will just return an empty string.
     *
     * @param string $pathAndFilename Path and filename of the TypoScript file
     * @return string The content of the .ts2 file, plus one chr(10) at the end
     */
    protected function readExternalTypoScriptFile($pathAndFilename)
    {
        return (is_file($pathAndFilename)) ? Files::getFileContents($pathAndFilename) . chr(10) : '';
    }

    /**
     * Generate TypoScript prototype definitions for all node types
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
            $code .= $this->generateTypoScriptForNodeType($nodeType);
        }
        return $code;
    }

    /**
     * Generate a TypoScript prototype definition for a given node type
     *
     * A prototype will be rendererd with the generator-class defined in the
     * nodeType-configuration 'fusion.prototypeGenerator'
     *
     * @param NodeType $nodeType
     * @return string
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function generateTypoScriptForNodeType(NodeType $nodeType)
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
     * Concatenate the given TypoScript resources with include statements
     *
     * @param array $typoScriptResources An array of TypoScript resource URIs
     * @return string A string of include statements for all resources
     */
    protected function getTypoScriptIncludes(array $typoScriptResources)
    {
        $code = chr(10);
        foreach ($typoScriptResources as $typoScriptResource) {
            $code .= 'include: ' . (string)$typoScriptResource . chr(10);
        }
        $code .= chr(10);
        return $code;
    }

    /**
     * Prepares an array with TypoScript paths to auto include before the Site TypoScript.
     *
     * @return array
     */
    protected function prepareAutoIncludeTypoScript()
    {
        $autoIncludeTypoScript = array();
        foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
            if (isset($this->autoIncludeConfiguration[$packageKey]) && $this->autoIncludeConfiguration[$packageKey] === true) {
                $autoIncludeTypoScript[] = sprintf($this->autoIncludeTypoScriptPattern, $packageKey);
            }
        }

        return $autoIncludeTypoScript;
    }

    /**
     * Set the pattern for including the site root TypoScript
     *
     * @param string $siteRootTypoScriptPattern A string for the sprintf format that takes the site package key as a single placeholder
     * @return void
     */
    public function setSiteRootTypoScriptPattern($siteRootTypoScriptPattern)
    {
        $this->siteRootTypoScriptPattern = $siteRootTypoScriptPattern;
    }

    /**
     * Get the TypoScript resources that are included before the site TypoScript.
     *
     * @return array
     */
    public function getPrependTypoScriptIncludes()
    {
        return $this->prependTypoScriptIncludes;
    }

    /**
     * Set TypoScript resources that should be prepended before the site TypoScript,
     * it defaults to the Neos Root.ts2 TypoScript.
     *
     * @param array $prependTypoScriptIncludes
     * @return void
     */
    public function setPrependTypoScriptIncludes(array $prependTypoScriptIncludes)
    {
        $this->prependTypoScriptIncludes = $prependTypoScriptIncludes;
    }

    /**
     * Get TypoScript resources that will be appended after the site TypoScript.
     *
     * @return array
     */
    public function getAppendTypoScriptIncludes()
    {
        return $this->appendTypoScriptIncludes;
    }

    /**
     * Set TypoScript resources that should be appended after the site TypoScript,
     * this defaults to an empty array.
     *
     * @param array $appendTypoScriptIncludes An array of TypoScript resource URIs
     * @return void
     */
    public function setAppendTypoScriptIncludes(array $appendTypoScriptIncludes)
    {
        $this->appendTypoScriptIncludes = $appendTypoScriptIncludes;
    }
}
