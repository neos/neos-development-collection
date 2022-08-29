<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\ContentElement;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Fusion\ViewHelpers\FusionContextTrait;
use Neos\Neos\Service\ContentElementEditableService;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 *
 * The wrapper contains the property name which should be made editable, and is by default
 * a "div" tag. The tag to use can be given as `tag` argument to the ViewHelper.
 *
 * In live workspace this just renders a tag with the specified $tag-name containing the value of the given $property.
 * For logged in users with access to the Backend this also adds required attributes for the RTE to work.
 *
 * Note: when passing a node you have to make sure a metadata wrapper is used around this that matches the given node
 * (see contentElement.wrap - i.e. the WrapViewHelper).
 */
class EditableViewHelper extends AbstractTagBasedViewHelper
{
    use FusionContextTrait;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentElementEditableService
     */
    protected $contentElementEditableService;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();

        $this->registerArgument(
            'property',
            'string',
            'Name of the property to render. Note: If this tag has child nodes, they overrule this argument!',
            true
        );
        $this->registerArgument(
            'tag',
            'string',
            'The name of the tag that should be wrapped around the property. By default this is a <div>',
            false,
            'div'
        );
        $this->registerArgument(
            'node',
            Node::class,
            'The node of the content element. Optional, will be resolved from the Fusion context by default'
        );
    }

    /**
     * In live workspace this just renders a tag; for logged in users with access to the Backend this also adds required
     * attributes for the editing.
     *
     * @return string The rendered property with a wrapping tag.
     *                In the user workspace this adds some required attributes for the RTE to work
     * @throws ViewHelperException
     */
    public function render(): string
    {
        $this->tag->setTagName($this->arguments['tag']);
        $this->tag->forceClosingTag(true);
        $content = $this->renderChildren();

        $node = $this->arguments['node'] ?? $this->getNodeFromFusionContext();

        if ($node === null) {
            throw new ViewHelperException(
                'A node is required, but one was not supplied and could not be found in the Fusion context.',
                1408521638
            );
        }

        $propertyName = $this->arguments['property'];
        if ($content === null) {
            if (!$this->templateVariableContainer->exists($propertyName)) {
                throw new ViewHelperException(sprintf(
                    'The property "%1$s" was not set as a template variable. If you use this ViewHelper in a partial,'
                    . ' make sure to pass the node property "%1$s" as an argument.',
                    $propertyName
                ), 1384507046);
            }
            $content = $this->templateVariableContainer->get($propertyName);
        }
        $this->tag->setContent($content);

        return $this->contentElementEditableService->wrapContentProperty($node, $propertyName, $this->tag->render());
    }

    /**
     * @return Node
     * @throws ViewHelperException
     */
    protected function getNodeFromFusionContext(): Node
    {
        $node = $this->getContextVariable('node');
        if ($node === null) {
            throw new ViewHelperException(
                'This ViewHelper can only be used in a Fusion content element.'
                . 'You have to specify the "node" argument if it cannot be resolved from the Fusion context.',
                1385737102
            );
        }

        return $node;
    }
}
