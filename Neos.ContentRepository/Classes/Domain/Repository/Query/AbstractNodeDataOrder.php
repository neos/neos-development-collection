<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

abstract class AbstractNodeDataOrder implements NodeDataOrderInterface
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * @var string
     */
    protected $order;

    /**
     * NodePathOrder constructor.
     * @param string $order
     * @throws \Exception
     */
    public function __construct(string $order = self::ORDER_ASC)
    {
        if ($order !== self::ORDER_ASC && $order !== self::ORDER_DESC) {
            throw new \InvalidArgumentException(sprintf('Invalid order: %s', $order), 1541496086);
        }
        $this->order = $order;
    }
}
