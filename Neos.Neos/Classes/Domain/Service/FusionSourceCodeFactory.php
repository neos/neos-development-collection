<?php

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Flow\Annotations as Flow;

class FusionSourceCodeFactory
{
    /**
     * @Flow\InjectConfiguration("fusion.autoInclude")
     * @var array
     */
    protected $autoIncludeConfiguration = [];

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    public function createFromAutoIncludes(): FusionSourceCodeCollection
    {
        $sourcecode = FusionSourceCodeCollection::empty();
        foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
            if (isset($this->autoIncludeConfiguration[$packageKey]) && $this->autoIncludeConfiguration[$packageKey] === true) {
                $sourcecode = $sourcecode->union(
                    FusionSourceCodeCollection::tryFromPackageFusionRoot($packageKey)
                );
            }
        }
        return $sourcecode;
    }

    public function createFromRootNode(TraversableNodeInterface $rootNode): FusionSourceCodeCollection
    {
        return $this->createFromSite($this->findSiteForSiteNode($rootNode));
    }

    public function createFromSite(Site $site): FusionSourceCodeCollection
    {
        return FusionSourceCodeCollection::tryFromPackageFusionRoot($site->getSiteResourcesPackageKey());
    }

    /**
     * Generate Fusion prototype definitions for all node types
     *
     * Only fully qualified node types (e.g. MyVendor.MyPackage:NodeType) will be considered.
     *
     * @throws \Neos\Neos\Domain\Exception
     */
    public function createFromNodeTypeDefinitions(): FusionSourceCodeCollection
    {
        $fusion = [];
        foreach ($this->nodeTypeManager->getNodeTypes(false) as $nodeType) {
            $fusion[] = $this->generateFusionForNodeType($nodeType);
        }
        return new FusionSourceCodeCollection(...array_filter($fusion));
    }

    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * A prototype will be rendered with the generator-class defined in the
     * nodeType-configuration 'fusion.prototypeGenerator'
     *
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function generateFusionForNodeType(NodeType $nodeType): ?FusionSourceCode
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
            return FusionSourceCode::fromString($generator->generate($nodeType));
        }
        return null;
    }

    private function findSiteForSiteNode(TraversableNodeInterface $siteNode): Site
    {
        return $this->siteRepository->findOneByNodeName((string)$siteNode->getNodeName())
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->getNodeName()), 1677245517);
    }
}
