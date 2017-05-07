<?php
namespace Neos\Neos\TypeConverter;

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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * An Object Converter for nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class NodeConverter extends \Neos\ContentRepository\TypeConverter\NodeConverter
{
    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @var integer
     */
    protected $priority = 3;

    /**
     * @param array $source
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return array
     */
    protected function prepareContextProperties(array $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        $contextProperties = parent::prepareContextProperties($source, $configuration);
        if (!isset($source['__nodePath'])) {
            return $contextProperties;
        }

        $siteMatchingNodePath = $this->siteRepository->findOneByNodePath($source['__nodePath']);
        $contextProperties['currentSite'] = $siteMatchingNodePath;
        $contextProperties['currentDomain'] = ($siteMatchingNodePath instanceof Site) ? $siteMatchingNodePath->getPrimaryDomain() : null;

        return $contextProperties;
    }
}
