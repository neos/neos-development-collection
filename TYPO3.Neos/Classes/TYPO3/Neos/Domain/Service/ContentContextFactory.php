<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Exception\InvalidNodeContextException;

/**
 * ContentContextFactory which ensures contexts stay unique. Make sure to
 * get ContextFactoryInterface injected instead of this class.
 *
 * See \TYPO3\TYPO3CR\Domain\Service\ContextFactory->build for detailed
 * explanations about the usage.
 *
 * @Flow\Scope("singleton")
 */
class ContentContextFactory extends ContextFactory
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Repository\DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Repository\SiteRepository
     */
    protected $siteRepository;

    /**
     * The context implementation this factory will create
     *
     * @var string
     */
    protected $contextImplementation = ContentContext::class;

    /**
     * Creates the actual Context instance.
     * This needs to be overridden if the Builder is extended.
     *
     * @param array $contextProperties
     * @return ContentContext
     */
    protected function buildContextInstance(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);

        return new ContentContext(
            $contextProperties['workspaceName'],
            $contextProperties['currentDateTime'],
            $contextProperties['dimensions'],
            $contextProperties['targetDimensions'],
            $contextProperties['invisibleContentShown'],
            $contextProperties['removedContentShown'],
            $contextProperties['inaccessibleContentShown'],
            $contextProperties['currentSite'],
            $contextProperties['currentDomain']
        );
    }

    /**
     * Merges the given context properties with sane defaults for the context implementation.
     *
     * @param array $contextProperties
     * @return array
     */
    protected function mergeContextPropertiesWithDefaults(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);

        $defaultContextProperties = array(
            'workspaceName' => 'live',
            'currentDateTime' => $this->now,
            'dimensions' => array(),
            'targetDimensions' => array(),
            'invisibleContentShown' => false,
            'removedContentShown' => false,
            'inaccessibleContentShown' => false,
            'currentSite' => null,
            'currentDomain' => null
        );

        if (!isset($contextProperties['currentSite'])) {
            $defaultContextProperties = $this->setDefaultSiteAndDomainFromCurrentRequest($defaultContextProperties);
        }

        $mergedProperties = Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, true);

        $this->mergeDimensionValues($contextProperties, $mergedProperties);
        $this->mergeTargetDimensionContextProperties($contextProperties, $mergedProperties, $defaultContextProperties);

        return $mergedProperties;
    }

    /**
     * Determines the current domain and site from the request and sets the resulting values as
     * as defaults.
     *
     * @param array $defaultContextProperties
     * @return array
     */
    protected function setDefaultSiteAndDomainFromCurrentRequest(array $defaultContextProperties)
    {
        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $defaultContextProperties['currentSite'] = $currentDomain->getSite();
            $defaultContextProperties['currentDomain'] = $currentDomain;
        } else {
            $defaultContextProperties['currentSite'] = $this->siteRepository->findDefault();
        }

        return $defaultContextProperties;
    }


    /**
     * This creates the actual identifier and needs to be overridden by builders extending this.
     *
     * @param array $contextProperties
     * @return string
     */
    protected function getIdentifierSource(array $contextProperties)
    {
        ksort($contextProperties);
        $identifierSource = $this->contextImplementation;
        foreach ($contextProperties as $propertyName => $propertyValue) {
            if ($propertyName === 'dimensions') {
                $stringParts = array();
                foreach ($propertyValue as $dimensionName => $dimensionValues) {
                    $stringParts[] = $dimensionName . '=' . implode(',', $dimensionValues);
                }
                $stringValue = implode('&', $stringParts);
            } elseif ($propertyName === 'targetDimensions') {
                $stringParts = array();
                foreach ($propertyValue as $dimensionName => $dimensionValue) {
                    $stringParts[] = $dimensionName . '=' . $dimensionValue;
                }
                $stringValue = implode('&', $stringParts);
            } elseif ($propertyValue instanceof \DateTimeInterface) {
                $stringValue = $propertyValue->getTimestamp();
            } elseif ($propertyValue instanceof Site) {
                $stringValue = $propertyValue->getNodeName();
            } elseif ($propertyValue instanceof Domain) {
                $stringValue = $propertyValue->getHostname();
            } else {
                $stringValue = (string)$propertyValue;
            }
            $identifierSource .= ':' . $stringValue;
        }

        return $identifierSource;
    }

    /**
     * @param array $contextProperties
     * @return void
     * @throws InvalidNodeContextException
     */
    protected function validateContextProperties($contextProperties)
    {
        parent::validateContextProperties($contextProperties);

        if (isset($contextProperties['currentSite'])) {
            if (!$contextProperties['currentSite'] instanceof Site) {
                throw new InvalidNodeContextException('You tried to set currentSite in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Site object as value.', 1373145297);
            }
        }
        if (isset($contextProperties['currentDomain'])) {
            if (!$contextProperties['currentDomain'] instanceof Domain) {
                throw new InvalidNodeContextException('You tried to set currentDomain in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Domain object as value.', 1373145384);
            }
        }
    }
}
