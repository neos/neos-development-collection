.. _page-rendering:

================
Rendering A Page
================

This section explains how pages are rendered in Neos. More precisely, we show how to render a node of type
``Neos.Neos:Document``. The default page type in Neos (``Neos.NodeTypes:Page``) inherits from this type.
If you create custom document node types, they need to be a subtype of ``Neos.Neos:Document`` as well.
This section also explains how to implement custom rendering for your own document node types.


.. figure:: data:image/png;base64,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9913l8pB48ePt/MsNtz8WxYYAg/Xx7FyXUqCX/VpnHL74HTV9ttvb+6//36z7bbbxtHkEm3As0Lt9+zZ06DZTj755Lpn3Nz49ttvm/bt25tPP/10KZ/WXHNNM2PGDHPIIYeYn3/+2bz11ltLcJj8gX656667zIknnmjbd7bi6mCqBPcjjzxiRowYYZ5//vklBPdWW21lHnroISu4CWKLFi1M3759zT///GPjSJJhokPI/PTTT+bwww8306dPr9ikkY8cvhMJNonrX3/9ZS6//HJzwQUXLOVGHD6A3/LLL2/OPvts2/77779vDjjgAPszCylsVMrFwP7666+tUMq9EMnDhw83I0eOrEssLAZ33333BgtuksbWW29tOe/zigPfYv4lwS+f8Ym2TXHg/PPPN0888YT577//6n616qqrGoRPQy8WZzy3xhprNPTRirm/EL+uueYac8UVV5gVVljB/Prrr2X1J00cyxeIYrx7/fXXbc5hkb/MMsvkjSNz5muvvWZ23nlns9xyyy1xD4J72LBhFV2sSkMOq0/jNKRYU2gwUSiqrq624hRdFfdVrH36h8iPzmfRufGHH36w/EUbHHfccYZFDnmVa91117VzKAtGdOKee+5p/722ttYcffTR5uGHH66bc4866ihzzjnnWHEf15VJwU2VMFrtI0mvttpqZvbs2baaGAR34+jF4Ovdu7f5999/rfC97LLLlhDecSQzBlubNm1Mnz596pxk1cq/ffTRR7b6wg5Hly5d7O9ZSDEwmzZtahcDgwcPtn6x2mU3hBXttddeaw4++GCz5ZZb2mfcz5tssondEWFQnnXWWWb11Vc3N9xwgx2otHH66aeboUOHmmbNmpmFCxeaq666yj67//77W/HPQo9V88yZM63tAQMGmCOOOMLcfvvtdqXdoUMHa+/BBx80DO7oRT8XL15s7rnnHrtapwqeK7iL9XPgwIGGP/R94sSJ1n9iVsjPxiG+5FNx4FvMjyT4FUccSmmD4gCVRKqBuddTTz1lfv/99zpORH/++OOP7RhjEtlvv/3MbbfdZjbccEP7X3gK/7/77jvDgg0bTBZ33HGHXXAV4qKbjErxW/KeXH4htK+88krrEhMsMTj++OPLcjFNHMsXiGK8ixashgwZUjAn8jt2YVZaaSWbnxAl7LB8+eWXpnnz5qHCXYSBSfCLuaOYxmGe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
      :alt: Rendering flow example

.. HINT: To edit the image above, go to the URL below.
   The image is encoded in the URL itself, so make sure to update this URL as well once you're done. A new data url can
   be generated in draw.io by using "File>Embed>Image", the URL below is generated via "File>Publish>Link".
   https://www.draw.io/?lightbox=1&highlight=0000ff&edit=_blank&layers=1&nav=1&title=Untitled%20Diagram.xml#R3VdNj5swEP013MEGklybze4ednvYtOrZhVlwaxjkmCX019dgOwS5W1WqBMpyQPbzeD4ez9YQ0H11fpCsKZ8xBxGQMD8H9C4gZLOJ9XsAegMkYWiAQvLcQNEEHPkvsKAza3kOp5mhQhSKN3Mww7qGTM0wJiV2c7NXFPOoDSvAA44ZEz76jeeqNOiWbCb8EXhRushRujMr31n2s5DY1jZeQOjr%2BJjlijlfttBTyXLsriB6COheIiozqs57EAO1jjaz7%2F6d1UveEmr1LxtSs%2BGNiRZcxmNeqndcQK6psVOUqsQCayYOE%2FpprBcGj6GelaoSehjp4Q9Qqrdfl7UKNTR5eEJsrJ2JOQR6twwLnbCVmbWyqSomC7BW9EKhViZgBUr22kSCYIq%2Fzb0zq5HiYjfxpAeWqj%2FTRjzavr48ecxNvAxFdiVXcGzYmH6nj82cK%2BsRpILz31nw63MbrKj6%2BbSbFBw53ZVX6k3D%2Fydkc8M6or6O4oV0RD3a7jBrqyFbEn7Wt%2Br6mkrpaqLa3rCoYl9UyUKiij3a7tsTx3p1LdEoWU1LLtZNiinxxbRbSExR9LF4W%2BpmT%2FxDKHRTu%2FoZjNPtamdw53HyArp4qcsn4eOX5%2FU7qJQs1kLp6dTmj2tXv1L08Bs%3D

1. An URL is requested from Neos through an HTTP request.
2. The requested URL is resolved to a node. This works via the Frontend ``NodeController`` and the ``NodeConverter``
   of the Neos CR by translating the URL path to a node path, and then finding the node with this path. The document
   node resolution is completely done in the Neos core - usually, site integrators do not need to modify it.
3. The document node is passed to Fusion, which is the Neos rendering engine. Rendering always starts at the Fusion
   path ``root``. This rendering process is explained in detail below.
4. Fusion can render Fluid templates, which in turn can call Fusion again to render parts of themselves. This can go
   back and forth multiple times, even recursively.
5. Once Fusion has traversed the rendering tree fully, rendering is done and the rendered output (usually HTML, but
   Fusion can render arbitrary text formats) is sent back to the requester.


The ``root`` path
=================

You may already have seen a ``Root.fusion`` that contain a path ``page`` which is filled with an object of type ``Neos.Neos:Page``.
Here, the ``Neos.Neos:Page`` Fusion object is assigned to the path ``page``, telling the system that the Fusion object
``Page`` is responsible for further rendering::

  page = Neos.Neos:Page {
    head {
      [...]
    }
    body {
      [...]
    }
  }

Let's investigate how this rendering process happens.
Fusion always starts rendering at the fusion path ``root``. You can verify this by simply replacing the code in your
``Root.fusion`` file with this snippet::

  root = "Hello World!"

All page rendering will disappear and only the words "Hello World" will be rendered by Neos.

Using the  ``page`` path is not the recommended way to render your document node types anymore. We encourage you to define a prototype named after your document node type extending ``Neos.Neos:Page``. Read :ref:`rendering-custom-page-types` for further details and how to achieve this.

The root ``Neos.Fusion:Case`` object
====================================

The ``root`` path contains, by default, a ``Neos.Fusion:Case`` object. Here is a section from this object - to see the full implementation, check out the file ``DefaultFusion.fusion`` in the package ``Neos.Neos``, path ``Resources\Private\Fusion``. ::

  root = Neos.Fusion:Case {

    [...more matchers before...]

    documentType {
      condition = Neos.Fusion:CanRender {
        type = ${q(documentNode).property('_nodeType.name')}
      }
      type = ${q(documentNode).property('_nodeType.name')}
    }

    default {
      condition = TRUE
      renderPath = '/page'
    }
  }

If you do not know what a ``Case`` object does, you might want to have a look at the :ref:`neos-Fusion-reference`.
All paths in the ``Case`` object (so-called *matchers*) check a certain condition - the ``condition`` path in the matcher.
Matchers are evaluated one after another, until one condition evaluates to ``TRUE``. If it does, matcher's ``type``,
``renderer`` or ``renderPath`` path (whichever exists) will be evaluated. If no other condition matches, the ``default``
matcher is evaluated and points Fusion to the path ``page``. Rendering then continues with the ``page`` path, which is
by default generated in your site package's ``Root.fusion`` file. This is why, if you don't do anything else, rendering
begins at your ``page`` path.

The current best practice is to use the ``documentType`` matcher by defining your own Fusion prototypes for each document
type. This approach will be covered further below.

The page path and ``Neos.Neos:Page`` object
===========================================

The minimally needed Fusion for rendering a page looks as follows::

  page = Page {
    body {
      templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'
    }
  }


``Page`` expects one parameter to be set: The path of the Fluid template which is rendered inside the ``<body>`` of the
resulting HTML page.

If the template above is an empty file, the output shows how minimal Neos impacts the generated markup::

  <!DOCTYPE html>
  <html>
    <!--
        This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
        Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

        More information and contribution opportunities at https://www.neos.io
    -->
    <head>
      <meta charset="UTF-8" />
    </head>
    <body>
      <script src="/_Resources/Static/Packages/Neos.Neos/JavaScript/LastVisitedNode.js" data-neos-node="a319a653-ef38-448d-9d19-0894299068aa"></script>
    </body>
  </html>

It becomes clear that Neos gives as much control over the markup as possible to the integrator: No body markup, no
styles, only little Javascript to record the last visited page to redirect back to it after logging in. Except for
the charset meta tag nothing related to the content is output by default.

If the template file is filled with the following content::

  <h1>{title}</h1>

the body would contain a heading to output the title of the current page::

  <body>
    <h1>My first page</h1>
  </body>

Again, no added CSS classes, no wraps. Why ``{title}`` outputs the page title is covered in detail below.

Adding pre-rendered output to the page template
-----------------------------------------------

Of course the current template is still quite boring; it does not show any content or any menu. In order to change that,
the Fluid template is adjusted as follows::

  {namespace fusion=Neos\Fusion\ViewHelpers}
  {parts.menu -> f:format.raw()}
  <h1>{title}</h1>
  {content.main -> f:format.raw()}

Placeholders for the menu and the content have been added. Because the ``parts.menu`` and ``content.main`` refer to a
rendered Fusion path, the output needs to be passed through the ``f:format.raw()`` ViewHelper. The Fusion needs to be
adjusted as well::

  page = Neos.Neos:Page {
    body {
      templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'

      parts {
        menu = Neos.Neos:Menu
      }

      content {
        main = Neos.Neos:PrimaryContent {
          nodePath = 'main'
        }
      }
    }
  }

In the above Fusion, a Fusion object at ``page.body.parts.menu`` is defined to be of type ``Neos.Neos:Menu``.
It is exactly this Fusion object which is rendered, by specifying its relative path inside
``{parts.menu -> f:format.raw()}``.

Furthermore, the ``Neos.Neos:PrimaryContent`` Fusion object is used to render a Neos ContentRepository
``ContentCollection`` node. Through the ``nodePath`` property, the name of the Neos ContentRepository
``ContentCollection`` node to render is specified. As a result, the web page now contains a menu and the contents
of the main content collection.

The use of ``content`` and ``parts`` here is **just a convention**, the names can be
chosen freely. In the example ``content`` is used for the section where content is later
placed, and ``parts`` is for anything that is not *content* in the sense that it
will directly be edited in the content module of Neos.

The ``Neos.Neos:Page`` object in more detail
--------------------------------------------

To understand what the ``Neos.Neos:Page`` object actually does, it makes sense to look at its definition. We can find the
``Page`` prototype in the file ``Page.fusion`` in the path ``Resources\Private\Fusion`` inside the ``Neos.Neos``
package. Here is a snippet taken from this object's definition::

  prototype(Neos.Neos:Page) < prototype(Neos.Fusion:Http.Message) {

    # The content of the head tag, integrators can add their own head content in this array.
    head = Neos.Fusion:Array {
      # Link tags for stylesheets in the head should go here
      stylesheets = Neos.Fusion:Array

      # Script includes in the head should go here
      javascripts = Neos.Fusion:Array {
        @position = 'after stylesheets'
      }
    }

    # Content of the body tag. To be defined by the integrator.
    body = Neos.Fusion:Template {
      node = ${node}
      site = ${site}

      # Script includes before the closing body tag should go here
      javascripts = Neos.Fusion:Array

      # This processor appends the rendered javascripts Array to the rendered template
      @process.appendJavaScripts = ${value + this.javascripts}
    }
  }

By looking at this definition, we understand a bit more about how page rendering actually works. ``Neos.Neos:Page``
inherits from ``Neos.Fusion:Http.Message``, which in turn inherits from ``Neos.Fusion:Array``. ``Array`` fusion objects
just render their keys one after another, so the Page object just outputs whatever is in it. The ``Neos.Neos:Page`` object
renders the HTML framework, such as doctype, head and body tags, and also defines the default integration points for
site integrators - ``head`` and ``body`` as well as their inner objects. It is not by coincidence that these exact paths
are pre-filled with sensible defaults in site package's generated default ``Root.fusion`` files.

We can also see that the ``body`` object is a ``Neos.Fusion:Template``, which is why we have to set the template path
to a Fluid template which will be rendered as the body.

Rendering custom document types
===============================

There are two basic approaches to render different document types. We currently recommend to create a Fusion
prototype per custom page type, which is since Neos 4.0 automatically picked up by Neos (see below). The "old" way
involves adding one root matcher per document type, explicitly checking for the node type in the condition, and
redirecting Fusion to another render path. It is documented here for completeness' sake, but we do not recommend to use
it anymore.

Prototype-based rendering
-------------------------

Since Neos 4.0, the root ``Case`` object ships with a ``documentType`` matcher, which will automatically pick up and
render Fusion prototypes with the same name as the corresponding document node type, if they exist. This snippet
of Fusion in the root ``Case`` is responsible for it::

  root = Neos.Fusion:Case {

    [...]

    documentType {
      condition = Neos.Fusion:CanRender {
        type = ${q(documentNode).property('_nodeType.name')}
      }
      type = ${q(documentNode).property('_nodeType.name')}
    }

    [...]
  }

This means that if you have a custom page type ``Your.Site:CustomPage``, you simply have to create a Fusion prototype
with a matching name to get different rendering for it. We explain how to do this in more detail in the "How To" section
of the docs: :ref:`rendering-custom-page-types`

Explicit path rendering (discouraged)
-------------------------------------

Before document-based rendering, you had to add your own matchers to the root object to get different rendering::

  root.customPageType1 {
    condition = ${q(node).is('[instanceof Your.Site:CustomPage]')}
    renderPath = '/custom1'
  }

  custom1 < page
  custom1 {
    # output modified here...
  }

There are a number of disadvantages of doing this, which is why we recommend to stick to prototype-based rendering:

* We are polluting the ``root`` namespace, adding to the danger of path collision
* We need to copy and modify the ``page`` object for each new document type, which becomes messy
* The order of path copying is important, therefore introducing possibly unwanted side effects

Further Reading
===============

Details on how Fusion works and can be used can be found in the section :ref:`inside-fusion`.
:ref:`adjusting-output` shows how page, menu and content markup can be adjusted freely.
