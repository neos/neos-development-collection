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

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Neos\Domain\Model\Site;
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
     * @var PackageManager
     */
    protected $packageManager;

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
        $generatorClassName = $nodeType->getConfiguration('options.fusion.prototypeGenerator');
        if ($generatorClassName === null) {
            return null;
        }
        if (!class_exists($generatorClassName)) {
            throw new \Neos\Neos\Domain\Exception('Fusion prototype-generator Class ' . $generatorClassName . ' does not exist');
        }
        $generator = $this->objectManager->get($generatorClassName);
        if (!$generator instanceof DefaultPrototypeGeneratorInterface) {
            throw new \Neos\Neos\Domain\Exception('Fusion prototype-generator Class ' . $generatorClassName . ' does not implement interface ' . DefaultPrototypeGeneratorInterface::class);
        }
        return FusionSourceCode::fromString($generator->generate($nodeType));
    }
}
