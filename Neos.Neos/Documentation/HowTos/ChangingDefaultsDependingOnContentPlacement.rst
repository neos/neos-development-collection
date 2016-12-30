Changing Defaults Depending on Content Placement
================================================

Let's say we want to adjust our `YouTube` content element depending on the context: By default,
it renders in a standard YouTube video size; but when being used inside the sidebar of the page,
it should shrink to a width of 200 pixels. This is possible through *nested prototypes*::

	page.body.contentCollections.sidebar.prototype(My.Package:YouTube) {
	  width = '200'
	  height = '150'
	}

Essentially the above code can be read as: "For all YouTube elements inside the sidebar of the page,
set width and height".

Let's say we also want to adjust the size of the YouTube video when being used in a `ThreeColumn`
element. This time, we cannot make any assumptions about a fixed Fusion path being rendered,
because the `ThreeColumn` element can appear both in the main column, in the sidebar and nested
inside itself. However, we are able to *nest prototypes into each other*::

	prototype(ThreeColumn).prototype(My.Package:YouTube) {
	  width = '200'
	  height = '150'
	}

This essentially means: "For all YouTube elements which are inside ThreeColumn elements, set width
and height".

The two possibilities above can also be flexibly combined. Basically this composability allows to
adjust the rendering of websites and web applications very easily, without overriding templates completely.

After you have now had a head-first start into Fusion based on practical examples, it is now
time to step back a bit, and explain the internals of Fusion and why it has been built this way.
