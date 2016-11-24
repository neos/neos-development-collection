================
Rendering a Menu
================

The implementation of a menu is done in TypoScript and HTML, this gives an
flexibility in what can be rendered.

First of all you have to add a new element (with a name) in TypoScript that is
of type Menu. Then inside the TypoScript object you can set what kind of
rendering (templatePath) to use, an entryLevel and a maximumLevels properties.

TypoScript code::

    mainMenu = Menu
    mainMenu {
        templatePath = 'resource://VendorName.VendorSite/Private/Templates/TypoScriptObjects/MainMenu.html'
        entryLevel = 1
        maximumLevels = 0
    }

The example above sets first a templatePath for the mainMenu object, then the level
to start finding nodes from is set to level 1. It will only take nodes on the
current level because of the property maximumLevels is set to 0.

If you want a custom rendering of my menu items then you need to add a template.
This template renders a ul list that has a link to a node.

Full HTML code::

    {namespace neos=Neos\Neos\ViewHelpers}
    <ul class="nav">
        <f:for each="{items}" as="item">
            <li class="menu-item">
                <neos:link.node node="{item.node}" />
            </li>
        </f:for>
    </ul>

What is done is first to include a viewhelper to being able to link my
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
with a li tag with a class called menu-item. Then we use our viewhelper
to (which namespace is neos in this example) link it to a node in Neos.
The linking is set in the parameter node, the you can chose what should be
shown as a text for the link. In this case the label (default) of the
node is the text.

Wrapping and linking of node::

    <li class="menu-item">
        <neos:link.node node="{item.node}" />
    </li>
