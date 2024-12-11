<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node\Doctrine;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;

/**
 * A SQL generator to create a condition matching a node underneath a certain node type
 */
class DescendantOfTypeConditionGenerator implements SqlGeneratorInterface
{
    private array $nodetypes;

    /**
     * @param array $nodetypes
     */
    public function __construct(array $nodetypes)
    {
        $this->nodetypes = $nodetypes;
    }

    /**
     * Returns an SQL query part that matches all Nodes that are underneath one of the the given NodeType(s)
     *
     * @param DoctrineSqlFilter $sqlFilter
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias)
    {
        $nodetypeList = implode("','", $this->nodetypes);

        return "select * from public.neos_contentrepository_domain_model_nodedata n1
        JOIN public.neos_contentrepository_domain_model_nodedata n2 ON n1.path LIKE CONCAT(n2.path, '%')
        WHERE n2.nodetype in ('" . $nodetypeList . "')";
    }
}
