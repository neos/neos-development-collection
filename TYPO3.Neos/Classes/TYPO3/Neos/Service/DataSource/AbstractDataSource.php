<?php
namespace TYPO3\Neos\Service\DataSource;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\Controller\ControllerContext;

/**
 * Data source interface for getting data.
 *
 * @api
 */
abstract class AbstractDataSource implements DataSourceInterface
{

    /**
     * The identifier of the operation
     *
     * @var string
     * @api
     */
    protected static $identifier = null;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @return string the short name of the operation
     * @api
     * @throws \TYPO3\Neos\Exception
     */
    public static function getIdentifier()
    {
        if (!is_string(static::$identifier)) {
            throw new \TYPO3\Neos\Exception('Identifier in class ' . __CLASS__ . ' is empty.', 1414090236);
        }

        return static::$identifier;
    }

    /**
     * @param ControllerContext $controllerContext
     * @return void
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }
}
