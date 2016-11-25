<?php
namespace Neos\Neos\ViewHelpers\ContentElement;

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
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\Fusion\ViewHelpers\TypoScriptContextTrait;
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
    use TypoScriptContextTrait;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var AuthorizationService
     */
    protected $nodeAuthorizationService;

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
    }

    /**
     * In live workspace this just renders a tag; for logged in users with access to the Backend this also adds required
     * attributes for the editing.
     *
     * @param string $property Name of the property to render. Note: If this tag has child nodes, they overrule this argument!
     * @param string $tag The name of the tag that should be wrapped around the property. By default this is a <div>
     * @param NodeInterface $node The node of the content element. Optional, will be resolved from the TypoScript context by default.
     * @return string The rendered property with a wrapping tag. In the user workspace this adds some required attributes for the RTE to work
     * @throws ViewHelperException
     */
    public function render($property, $tag = 'div', NodeInterface $node = null)
    {
        $this->tag->setTagName($tag);
        $this->tag->forceClosingTag(true);
        $content = $this->renderChildren();

        if ($node === null) {
            $node = $this->getNodeFromTypoScriptContext();
        }

        if ($node === null) {
            throw new ViewHelperException('A node is required, but one was not supplied and could not be found in the TypoScript context.', 1408521638);
        }

        if ($content === null) {
            if (!$this->templateVariableContainer->exists($property)) {
                throw new ViewHelperException(sprintf('The property "%1$s" was not set as a template variable. If you use this ViewHelper in a partial, make sure to pass the node property "%1$s" as an argument.', $property), 1384507046);
            }
            $content = $this->templateVariableContainer->get($property);
        }
        $this->tag->setContent($content);

        return $this->contentElementEditableService->wrapContentProperty($node, $property, $this->tag->render());
    }

    /**
     * @return NodeInterface
     * @throws ViewHelperException
     */
    protected function getNodeFromTypoScriptContext()
    {
        $node = $this->getContextVariable('node');
        if ($node === null) {
            throw new ViewHelperException('This ViewHelper can only be used in a TypoScript content element. You have to specify the "node" argument if it cannot be resolved from the TypoScript context.', 1385737102);
        }

        return $node;
    }
}
