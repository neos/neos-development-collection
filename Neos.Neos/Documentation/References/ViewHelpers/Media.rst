.. _`Media ViewHelper Reference`:

Media ViewHelper Reference
==========================

This reference was automatically generated from code on 2021-05-27


.. _`Media ViewHelper Reference: neos.media:fileTypeIcon`:

neos.media:fileTypeIcon
-----------------------

Renders an <img> HTML tag for a file type icon for a given Neos.Media's asset instance

:Implementation: Neos\\Media\\ViewHelpers\\FileTypeIconViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``asset`` (Neos\Media\Domain\Model\AssetInterface, *optional*): An Asset object to determine the file type icon for. Alternatively $filename can be specified.

* ``filename`` (string, *optional*): A filename to determine the file type icon for. Alternatively $asset can be specified.

* ``width`` (integer, *optional*): Desired width of the icon

* ``height`` (integer, *optional*): Desired height of the icon




Examples
********

**Rendering an asset file type icon**::

	<neos.media:fileTypeIcon asset="{assetObject}" height="16" />


Expected result::

	(depending on the asset, no scaling applied)
	<img src="_Resources/Static/Packages/Neos/Media/Icons/16px/jpg.png" height="16" alt="file type alt text" />


**Rendering a file type icon by given filename**::

	<neos.media:fileTypeIcon filename="{someFilename}" height="16" />


Expected result::

	(depending on the asset, no scaling applied)
	<img src="_Resources/Static/Packages/Neos/Media/Icons/16px/jpg.png" height="16" alt="file type alt text" />




.. _`Media ViewHelper Reference: neos.media:form.checkbox`:

neos.media:form.checkbox
------------------------

View Helper which creates a simple checkbox (<input type="checkbox">).

:Implementation: Neos\\Media\\ViewHelpers\\Form\\CheckboxViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed): Value of input tag. Required for checkboxes

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``checked`` (boolean, *optional*): Specifies that the input element should be preselected

* ``multiple`` (boolean, *optional*): Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)




Examples
********

**Example**::

	<neos.media:form.checkbox name="myCheckBox" value="someValue" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" />


**Preselect**::

	<neos.media:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
	(depending on $object)


**Bind to object property**::

	<neos.media:form.checkbox property="interests" value="Neos" />


Expected result::

	<input type="checkbox" name="user[interests][]" value="Neos" checked="checked" />
	(depending on property "interests")




.. _`Media ViewHelper Reference: neos.media:format.relativeDate`:

neos.media:format.relativeDate
------------------------------

Renders a DateTime formatted relative to the current date

:Implementation: Neos\\Media\\ViewHelpers\\Format\\RelativeDateViewHelper




Arguments
*********

* ``date`` (DateTimeInterface, *optional*): The date to be formatted




.. _`Media ViewHelper Reference: neos.media:image`:

neos.media:image
----------------

Renders an <img> HTML tag from a given Neos.Media's image instance

:Implementation: Neos\\Media\\ViewHelpers\\ImageViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``alt`` (string): Specifies an alternate text for an image

* ``ismap`` (string, *optional*): Specifies an image as a server-side image-map. Rarely used. Look at usemap instead

* ``usemap`` (string, *optional*): Specifies an image as a client-side image-map

* ``loading`` (string, *optional*): Specifies the loading attribute for an image

* ``image`` (Neos\Media\Domain\Model\ImageInterface, *optional*): The image to be rendered as an image

* ``width`` (integer, *optional*): Desired width of the image

* ``maximumWidth`` (integer, *optional*): Desired maximum width of the image

* ``height`` (integer, *optional*): Desired height of the image

* ``maximumHeight`` (integer, *optional*): Desired maximum height of the image

* ``allowCropping`` (boolean, *optional*): Whether the image should be cropped if the given sizes would hurt the aspect ratio

* ``allowUpScaling`` (boolean, *optional*): Whether the resulting image size might exceed the size of the original asset

* ``async`` (boolean, *optional*): Return asynchronous image URI in case the requested image does not exist already

* ``preset`` (string, *optional*): Preset used to determine image configuration

* ``quality`` (integer, *optional*): Quality of the image, from 0 to 100

* ``format`` (string, *optional*): Format for the image, jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported




Examples
********

**Rendering an image as-is**::

	<neos.media:image image="{imageObject}" alt="a sample image without scaling" />


Expected result::

	(depending on the image, no scaling applied)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample image without scaling" />


**Rendering an image with scaling at a given width only**::

	<neos.media:image image="{imageObject}" maximumWidth="80" alt="sample" />


Expected result::

	(depending on the image; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />


**Rendering an image with scaling at given width and height, keeping aspect ratio**::

	<neos.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" alt="sample" />


Expected result::

	(depending on the image; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />


**Rendering an image with crop-scaling at given width and height**::

	<neos.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />


Expected result::

	(depending on the image; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />


**Rendering an image with allowed up-scaling at given width and height**::

	<neos.media:image image="{imageObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />


Expected result::

	(depending on the image; scaled up or down to a width 5000 pixels, keeping aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />




.. _`Media ViewHelper Reference: neos.media:thumbnail`:

neos.media:thumbnail
--------------------

Renders an <img> HTML tag from a given Neos.Media's asset instance

:Implementation: Neos\\Media\\ViewHelpers\\ThumbnailViewHelper




Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``data`` (array, *optional*): Additional data-* attributes. They will each be added with a "data-" prefix.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``alt`` (string): Specifies an alternate text for an asset

* ``asset`` (Neos\Media\Domain\Model\AssetInterface): The asset to be rendered as a thumbnail

* ``width`` (integer, *optional*): Desired width of the thumbnail

* ``maximumWidth`` (integer, *optional*): Desired maximum width of the thumbnail

* ``height`` (integer, *optional*): Desired height of the thumbnail

* ``maximumHeight`` (integer, *optional*): Desired maximum height of the thumbnail

* ``allowCropping`` (boolean, *optional*): Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio

* ``allowUpScaling`` (boolean, *optional*): Whether the resulting thumbnail size might exceed the size of the original asset

* ``async`` (boolean, *optional*): Return asynchronous image URI in case the requested image does not exist already

* ``preset`` (string, *optional*): Preset used to determine image configuration

* ``quality`` (integer, *optional*): Quality of the image, from 0 to 100




Examples
********

**Rendering an asset thumbnail**::

	<neos.media:thumbnail asset="{assetObject}" alt="a sample asset without scaling" />


Expected result::

	(depending on the asset, no scaling applied)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample asset without scaling" />


**Rendering an asset thumbnail with scaling at a given width only**::

	<neos.media:thumbnail asset="{assetObject}" maximumWidth="80" alt="sample" />


Expected result::

	(depending on the asset; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />


**Rendering an asset thumbnail with scaling at given width and height, keeping aspect ratio**::

	<neos.media:thumbnail asset="{assetObject}" maximumWidth="80" maximumHeight="80" alt="sample" />


Expected result::

	(depending on the asset; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />


**Rendering an asset thumbnail with crop-scaling at given width and height**::

	<neos.media:thumbnail asset="{assetObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />


Expected result::

	(depending on the asset; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />


**Rendering an asset thumbnail with allowed up-scaling at given width and height**::

	<neos.media:thumbnail asset="{assetObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />


Expected result::

	(depending on the asset; scaled up or down to a width 5000 pixels, keeping aspect ratio)
	<img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />




.. _`Media ViewHelper Reference: neos.media:uri.image`:

neos.media:uri.image
--------------------

Renders the src path of a thumbnail image of a given Neos.Media image instance

:Implementation: Neos\\Media\\ViewHelpers\\Uri\\ImageViewHelper




Arguments
*********

* ``image`` (Neos\Media\Domain\Model\ImageInterface, *optional*): The image to be rendered as an image

* ``width`` (integer, *optional*): Desired width of the image

* ``maximumWidth`` (integer, *optional*): Desired maximum width of the image

* ``height`` (integer, *optional*): Desired height of the image

* ``maximumHeight`` (integer, *optional*): Desired maximum height of the image

* ``allowCropping`` (boolean, *optional*): Whether the image should be cropped if the given sizes would hurt the aspect ratio

* ``allowUpScaling`` (boolean, *optional*): Whether the resulting image size might exceed the size of the original asset

* ``async`` (boolean, *optional*): Return asynchronous image URI in case the requested image does not exist already

* ``preset`` (string, *optional*): Preset used to determine image configuration

* ``quality`` (integer, *optional*): Quality of the image, from 0 to 100

* ``format`` (string, *optional*): Format for the image, jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported




Examples
********

**Rendering an image path as-is**::

	{neos.media:uri.image(image: imageObject)}


Expected result::

	(depending on the image)
	_Resources/Persistent/b29[...]95d.jpeg


**Rendering an image path with scaling at a given width only**::

	{neos.media:uri.image(image: imageObject, maximumWidth: 80)}


Expected result::

	(depending on the image; has scaled keeping the aspect ratio)
	_Resources/Persistent/b29[...]95d.jpeg




.. _`Media ViewHelper Reference: neos.media:uri.thumbnail`:

neos.media:uri.thumbnail
------------------------

Renders the src path of a thumbnail image of a given Neos.Media asset instance

:Implementation: Neos\\Media\\ViewHelpers\\Uri\\ThumbnailViewHelper




Arguments
*********

* ``asset`` (Neos\Media\Domain\Model\AssetInterface): The asset to be rendered as a thumbnail

* ``width`` (integer, *optional*): Desired width of the thumbnail

* ``maximumWidth`` (integer, *optional*): Desired maximum width of the thumbnail

* ``height`` (integer, *optional*): Desired height of the thumbnail

* ``maximumHeight`` (integer, *optional*): Desired maximum height of the thumbnail

* ``allowCropping`` (boolean, *optional*): Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio

* ``allowUpScaling`` (boolean, *optional*): Whether the resulting thumbnail size might exceed the size of the original asset

* ``async`` (boolean, *optional*): Return asynchronous image URI in case the requested image does not exist already

* ``preset`` (string, *optional*): Preset used to determine image configuration

* ``quality`` (integer, *optional*): Quality of the image




Examples
********

**Rendering an asset thumbnail path as-is**::

	{neos.media:uri.thumbnail(asset: assetObject)}


Expected result::

	(depending on the asset)
	_Resources/Persistent/b29[...]95d.jpeg


**Rendering an asset thumbnail path with scaling at a given width only**::

	{neos.media:uri.thumbnail(asset: assetObject, maximumWidth: 80)}


Expected result::

	(depending on the asset; has scaled keeping the aspect ratio)
	_Resources/Persistent/b29[...]95d.jpeg



