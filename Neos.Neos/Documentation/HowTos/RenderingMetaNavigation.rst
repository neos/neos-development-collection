===========================
Rendering a Meta-Navigation
===========================

To render a meta navigation (ex: footer navigation)
in Neos all you need to use is TypoScript and
HTML.

A common fact is that most sites have footer where all
pages are using the same content or information. So a
common issue is how to solve this in the best possible
way.

VendorName.VendorSite/Resources/Private/TypoScripts/Library/Root.fusion

TypoScript code::

    page.body {
        metaMenu = Menu
        metaMenu {
            entryLevel = 2
            templatePath = 'resource://VendorName.VendorSite/Private
            /Templates/TypoScriptObjects/MetaMenu.html'
            maximumLevels = 1
            startingPoint = ${q(site).children('[uriPathSegment="metamenu"]').get(0)}
        }
    }


The first thing that we define inside the page.body is a Menu object
that is called metaMenu. The options available in this example is:

* entryLevel: On which level in the page structure the menu should
  start.
* templatePath: The path to the template where the rendering is
  done.
* maximumLevels: How many levels the menu can show.
* startingPoint: The starting point of the menu, in this case the
  node with name 'nameOfNode' is the starting point.

HTML template code::

    {namespace neos=Neos\Neos\ViewHelpers}
    <nav class="nav">
        <ul class="nav nav-pills">
            <f:for each="{items}" as="item" iteration="menuItemIterator">
                <li class="{item.state}">
                    <neos:link.node node="{item.node}" />
                </li>
            </f:for>
        </ul>
    </nav>

What is done is first to include a view helper to be able to link to
nodes inside the HTML. The namespace in the example is neos to
clarify from where the viewhelper is taken.

Viewhelper include::

    {namespace neos=Neos\Neos\ViewHelpers}

The next thing is to iterate through the nodes found by TypoScript.

Iterating through nodes::

    <f:for each="{items}" as="item">
        ...
    </f:for>

What then is done inside the iteration is that first we wrap our node
with a li tag with a class called menu-item. Then we use our view helper
to (which namespace is neos that is clarified) link it to a node in Neos.
The linking is set in the parameter node, the you can choose what should be
shown as a text for the link. In this case the label (default) of the
node is the text.

Wrapping and linking of node::

    <li class="{item.state}">
        <neos:link.node node="{item.node}" />
    </li>

The last thing to do is to include the meta menu to our page layout(s).

Include meta menu::

    {parts.metaMenu -> f:format.raw()}
