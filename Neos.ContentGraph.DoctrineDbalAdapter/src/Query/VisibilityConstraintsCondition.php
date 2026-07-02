<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Flowpack\QueryObjectBuilder\MySQL\Builder\Exp;
use Flowpack\QueryObjectBuilder\MySQL\Builder\SqlBuilder;
use Flowpack\QueryObjectBuilder\MySQL\Q;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

final readonly class VisibilityConstraintsCondition implements Exp
{
    private function __construct(
        private VisibilityConstraints $visibilityConstraints,
        private Alias $alias,
    ) {
    }

    public static function from(
        VisibilityConstraints $visibilityConstraints,
    ): self {
        return new self(
            visibilityConstraints: $visibilityConstraints,
            alias: Alias::none()
        );
    }

    public function hierarchyAlias(string $alias): self
    {
        return new self(
            visibilityConstraints: $this->visibilityConstraints,
            alias: Alias::from($alias),
        );
    }

    public function writeSql(SqlBuilder $sb): void
    {
        $q = Q::not(
            Q\Func::jsonContainsPath(
                $this->alias->resolve(Q::n('subtreetags')),
                Q::string('one'),
                // TODO Q::args() would not work here
                ...$this->visibilityConstraints->excludedSubtreeTags->map(
                    fn (SubtreeTag $subtreeTag) => Q::arg(
                        SubtreeTagPath::create($subtreeTag)
                    )
                )
            )
        );

        $q->writeSql($sb);
    }
}
