<?php

declare(strict_types=1);

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

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Neos\Domain\Exception as NeosDomainException;
use Neos\Neos\Domain\Model\Site;

/**
 * @internal For interacting with Fusion from the outside a FusionView should be used.
 */
class FusionSourceCodeFactory
{
    /**
     * @var array<string, mixed>
     */
    #[Flow\InjectConfiguration("fusion.autoInclude")]
    protected array $autoIncludeConfiguration = [];

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\Inject]
    protected ObjectManagerInterface $objectManager;

    public function createFromAutoIncludes(): FusionSourceCodeCollection
    {
        $sourcecode = FusionSourceCodeCollection::empty();
        foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
            if (isset($this->autoIncludeConfiguration[$packageKey]) && $this->autoIncludeConfiguration[$packageKey] === true) {
                $sourcecode = $sourcecode->union(
                    FusionSourceCodeCollection::tryFromPackageRootFusion($packageKey)
                );
            }
        }
        return $sourcecode;
    }

    public function createFromSite(Site $site): FusionSourceCodeCollection
    {
        return FusionSourceCodeCollection::tryFromPackageRootFusion($site->getSiteResourcesPackageKey());
    }

    /**
     * Generate Fusion prototype definitions for all node types
     *
     * Only fully qualified node types (e.g. MyVendor.MyPackage:NodeType) will be considered.
     *
     * @throws NeosDomainException
     */
    public function createFromNodeTypeDefinitions(ContentRepositoryId $contentRepositoryId): FusionSourceCodeCollection
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $fusion = [];
        foreach ($contentRepository->getNodeTypeManager()->getNodeTypes(false) as $nodeType) {
            $fusion[] = $this->tryCreateFromNodeTypeDefinition($nodeType);
        }
        return new FusionSourceCodeCollection(...array_filter($fusion));
    }

    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * A prototype will be rendered with the generator-class defined in the
     * nodeType-configuration 'fusion.prototypeGenerator'
     *
     * @throws NeosDomainException
     */
    private function tryCreateFromNodeTypeDefinition(NodeType $nodeType): ?FusionSourceCode
    {
        $generatorClassName = $nodeType->getConfiguration('options.fusion.prototypeGenerator');
        if ($generatorClassName === null) {
            return null;
        }
        if (!class_exists($generatorClassName)) {
            throw new NeosDomainException('Fusion prototype-generator Class ' . $generatorClassName . ' does not exist');
        }
        $generator = $this->objectManager->get($generatorClassName);
        if (!$generator instanceof DefaultPrototypeGeneratorInterface) {
            throw new NeosDomainException('Fusion prototype-generator Class ' . $generatorClassName . ' does not implement interface ' . DefaultPrototypeGeneratorInterface::class);
        }
        return FusionSourceCode::fromString($generator->generate($nodeType));
    }
}
