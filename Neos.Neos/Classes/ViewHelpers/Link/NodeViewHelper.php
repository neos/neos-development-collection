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

namespace Neos\Neos\ViewHelpers\Link;

/**
 * A view helper for creating links with URIs pointing to nodes.
 *
 * The target node can be provided as string or as a Node object; if not specified
 * at all, the generated URI will refer to the current document node inside the Fusion context.
 *
 * When specifying the ``node`` argument as string, the following conventions apply:
 *
 * *``node`` starts with ``/``:*
 * The given path is an absolute node path and is treated as such.
 * Example: ``/sites/acmecom/home/about/us``
 *
 * *``node`` does not start with ``/``:*
 * The given path is treated as a path relative to the current node.
 * Examples: given that the current node is ``/sites/acmecom/products/``,
 * ``stapler`` results in ``/sites/acmecom/products/stapler``,
 * ``../about`` results in ``/sites/acmecom/about/``,
 * ``./neos/info`` results in ``/sites/acmecom/products/neos/info``.
 *
 * *``node`` starts with a tilde character (``~``):*
 * The given path is treated as a path relative to the current site node.
 * Example: given that the current node is ``/sites/acmecom/products/``,
 * ``~/about/us`` results in ``/sites/acmecom/about/us``,
 * ``~`` results in ``/sites/acmecom``.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:link.node>some link</neos:link.node>
 * </code>
 * <output>
 * <a href="sites/mysite.com/homepage/about.html">some link</a>
 * (depending on current node, format etc.)
 * </output>
 *
 * <code title="Generating a link with an absolute URI">
 * <neos:link.node absolute="{true}">bookmark this page</neos:link.node>
 * </code>
 * <output>
 * <a href="http://www.example.org/homepage/about.html">bookmark this page</a>
 * (depending on current workspace, current node, format, host etc.)
 * </output>
 *
 * <code title="Target node given as absolute node path">
 * <neos:link.node node="/sites/exampleorg/contact/imprint">Corporate imprint</neos:link.node>
 * </code>
 * <output>
 * <a href="contact/imprint.html">Corporate imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Target node given as node://-uri">
 * <neos:link.node node="node://30e893c1-caef-0ca5-b53d-e5699bb8e506">Corporate imprint</neos:link.node>
 * </code>
 * <output>
 * <a href="contact/imprint.html">Corporate imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Target node given as relative node path">
 * <neos:link.node node="~/about/us">About us</neos:link.node>
 * </code>
 * <output>
 * <a href="about/us.html">About us</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Node label as tag content">
 * <neos:link.node node="/sites/exampleorg/contact/imprint" />
 * </code>
 * <output>
 * <a href="contact/imprint.html">Imprint</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * <code title="Dynamic tag content involving the linked node's properties">
 * <neos:link.node node="about-us">see our <span>{linkedNode.label}</span> page</neos:link.node>
 * </code>
 * <output>
 * <a href="about-us.html">see our <span>About Us</span> page</a>
 * (depending on current workspace, current node, format etc.)
 * </output>
 *
 * @deprecated will be removed with Neos 10 use \Neos\Neos\FluidAdapter\ViewHelpers\Link\NodeViewHelper
 * @api
 */
class NodeViewHelper extends \Neos\Neos\FluidAdapter\ViewHelpers\Link\NodeViewHelper
{
}
