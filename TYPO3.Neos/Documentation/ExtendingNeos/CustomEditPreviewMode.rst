.. _custom-edit-preview-mode:

Custom Edit/Preview-Modes
=========================

From the beginning the Neos backend was designed to be extensible with different rendering modes users can switch
depending on their use-case. In-place editing and the raw-content-editing-mode are only a small glimpse of what is possible.

It is encouraged to add custom edit- or preview modes. Use-cases could be the preview of the content in search engines or
on mobile devices.

Add a custom Preview Mode
-------------------------

Edit/preview modes are added to the Neos-Backend via *Settings.yaml*.

.. code-block:: yaml

  TYPO3:
    Neos:
      userInterface:
        editPreviewModes:
          print:
            title: 'Print'
            # show as edit mode
            isEditingMode: FALSE
            # show as preview mode
            isPreviewMode: TRUE
            # render path
            typoScriptRenderingPath: 'print'
            # show after the existing modes
            position: 200

The settings ``isEditingMode`` and ``isPreviewMode`` are controlling whether the mode will show up in the section "Edit"
or "Preview" of the Neos-Backend. The major difference between both sections is that inside "Preview" section the inline
editing options are not activated.

The actual rendering of the edit/preview mode is configured in TypoScript::

	print < page
	print {
		head {
			stylesheets.printCss = TYPO3.TypoScript:Tag {
				@position = 'end 10'
				tagName = 'link'
				attributes {
					media = 'all'
					rel = 'stylesheet'
					href = TYPO3.TypoScript:ResourceUri {
						path = 'resource://Neos.Demo/Public/Styles/Print.css'
					}
				}
			}
		}
	}

In this example the default rendering as defined in the path ``page`` is used and altered to include the Print.css for
all media.

Add a custom Editing Mode
-------------------------

To add an editing mode instead of a preview mode the configuration in *Settings.yaml* has to be changed.

 .. code-block:: yaml

  TYPO3:
    Neos:
      userInterface:
        editPreviewModes:
          print:
            isEditingMode: TRUE
            isPreviewMode: FALSE

.. warning:: It is currently possible to configure an edit/preview-mode for editing and preview at the same time. We are
	still unsure whether this is a bug or a feature -- so this behavior may change in future releases.

