<?php
namespace TYPO3\Neos\NodeTypes\Service\DataSource;

/*
 * This file is part of the TYPO3.Neos.NodeTypes package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

class FormDefinitionDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    protected static $identifier = 'neos-nodetypes-form-definitions';

    /**
     * @Flow\Inject
     * @var \TYPO3\Form\Persistence\YamlPersistenceManager
     */
    protected $yamlPersistenceManager;

    /**
     * @param NodeInterface|null $node
     * @param array $arguments
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $formDefinitions['']['label'] = '';
        $forms = $this->yamlPersistenceManager->listForms();

        foreach ($forms as $form) {
            $formDefinitions[$form['identifier']]['label'] = $form['name'];
        }

        return $formDefinitions;
    }
}
