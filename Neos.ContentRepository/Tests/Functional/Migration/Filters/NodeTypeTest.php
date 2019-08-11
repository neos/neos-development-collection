<?php
namespace Neos\ContentRepository\Tests\Functional\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Query\Expr;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Migration\Filters\NodeType as NodeTypeFilter;

/**
 * Testcase for the NodeService
 *
 */
class NodeTypeTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
    }

    public function getFilterExpressionsDataprovider(): array
    {
        return [
            'nodeTypeOnly' => [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'withSubTypes' => false,
                'exclude' => false,
                'expected' => 'n0_.nodetype IN (\'Neos.ContentRepository.Testing:Page\')',
            ],
            'nodeTypeAndSubTypes' => [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'withSubTypes' => true,
                'exclude' => false,
                'expected' => 'n0_.nodetype IN (\'Neos.ContentRepository.Testing:Page\', \'Neos.ContentRepository.Testing:Chapter\', \'Neos.ContentRepository.Testing:PageWithConfiguredLabel\')',
            ],
            'nodeTypeExclude' => [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'withSubTypes' => false,
                'exclude' => true,
                'expected' => 'NOT (n0_.nodetype IN (\'Neos.ContentRepository.Testing:Page\'))',
            ],
            'nodeTypeExcludeAndSubTypes' => [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'withSubTypes' => true,
                'exclude' => true,
                'expected' => 'NOT (n0_.nodetype IN (\'Neos.ContentRepository.Testing:Page\', \'Neos.ContentRepository.Testing:Chapter\', \'Neos.ContentRepository.Testing:PageWithConfiguredLabel\'))',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFilterExpressionsDataprovider
     * @param string $nodeType
     * @param bool $withSubTypes
     * @param bool $exclude
     * @param string $expected
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function getFilterExpressions(string $nodeType, bool $withSubTypes, bool $exclude, string $expected)
    {
        $nodeTypeFilter = new NodeTypeFilter();
        $nodeTypeFilter->setNodeType($nodeType);
        $nodeTypeFilter->setWithSubTypes($withSubTypes);
        $nodeTypeFilter->setExclude($exclude);

        $filterExpressions = $nodeTypeFilter->getFilterExpressions(new Query(NodeData::class));
        $query = new Query(NodeData::class);
        $query->matching(call_user_func_array([new Expr(), 'andX'], $filterExpressions));

        $actual = $query->getSql();
        self::assertStringEndsWith(' WHERE ' . $expected, $actual);
    }
}
