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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\PositionalArraySorter;

/**
 * Find nodes based on a fulltext search
 *
 * @Flow\Scope("singleton")
 */
class NodeSearchService implements NodeSearchServiceInterface
{

    #[Flow\InjectConfiguration('NodeSearch.resolvers', 'Neos.Neos')]
    protected array $resolvers;

    /**
     * Search all properties for given $term
     *
     * TODO: Implement a better search when Flow offer the possibility
     *
     * @param string|array $term search term
     * @param string[] $searchNodeTypes
     * @return NodeInterface[]
     */
    public function findByProperties(
        $term,
        array $searchNodeTypes,
        Context $context,
        NodeInterface $startingPoint = null
    ) {
        if (empty($term)) {
            throw new \InvalidArgumentException('"term" cannot be empty: provide a term to search for.', 1421329285);
        }

        $searchResult = [];
        $searchTerms = is_string($term) ? [$term] : $term;
        $sortedResolvers = (new PositionalArraySorter($this->resolvers))->toArray();

        // Instantiate each configured resolver
        /** @var NodeSearchResolverInterface[] $resolverInstances */
        $resolverInstances = array_reduce($sortedResolvers, static function (array $carry, array $resolverConfig) {
            try {
                $resolverInstance = new $resolverConfig['class']();
                if ($resolverInstance instanceof NodeSearchResolverInterface) {
                    $carry[] = $resolverInstance;
                }
            } catch (\Exception) {
                // TODO: Log invalid resolver configuration
            }
            return $carry;
        }, []);

        // Iterate over each search term and try to resolve it with the resolvers
        foreach ($searchTerms as $searchTerm) {
            foreach ($resolverInstances as $resolver) {
                if ($resolver->matches($searchTerm, $searchNodeTypes, $context, $startingPoint)) {
                    $resolverResults = $resolver->resolve(
                        $searchTerm,
                        $searchNodeTypes,
                        $context,
                        $startingPoint
                    );
                    // If the resolver returns results, we can skip the other resolvers for the current term
                    if ($resolverResults) {
                        $searchResult = array_merge($searchResult, $resolverResults);
                        break;
                    }
                }
            }
        }

        // TODO: Sort results?

        return $searchResult;
    }
}
