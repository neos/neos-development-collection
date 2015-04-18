.. _1.0.0-alpha3:

=========================
1.0.0-alpha3 (2013-02-15)
=========================

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Base Distribution
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Remove Jenkins repository from  composer manifest
-----------------------------------------------------------------------------------------

Since our packages are on Packagist now, the Satis repository on Jenkins
should no longer be used by the public.

* Related: `#44022 <http://forge.typo3.org/issues/44022>`_
* Commit: `f677a45 <http://git.typo3.org/TYPO3v5/Distributions/Base.git?a=commit;h=f677a456b036af6542f1b8ad35326ce0b14e1815>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Fix margin of inspector & tree panel in preview mode
-----------------------------------------------------------------------------------------

* Commit: `801532e <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=801532e766d82f80cb7463183cfcc4994f8144a6>`_

[FEATURE] Improve handling of images in the inspector
-----------------------------------------------------------------------------------------

* Fixes: `#44174 <http://forge.typo3.org/issues/44174>`_
* Commit: `b54b59c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=b54b59cde7258e5a7d4931d634fafa58f46ef74b>`_

[BUGFIX] Fix height and overflow for page tree
-----------------------------------------------------------------------------------------

* Related: `#44994 <http://forge.typo3.org/issues/44994>`_
* Commit: `9930c9c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=9930c9c4cd53d09ec993cfd53e0d355fb55337b5>`_

[BUGFIX] Add missing migration for domain host pattern identity
-----------------------------------------------------------------------------------------

The change I1235a121844c64142de86b81cf92ddfefe561aac introduced
an Identity annotation on the hostPattern property of Domain but
the commit is lacking the necessary migration.

* Commit: `d464ba8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=d464ba8d450304e7dcefa948f27fb7c2d34ac5b1>`_

[TASK] Make the pagetree push adjust the body margin
-----------------------------------------------------------------------------------------

When the  Page Tree is sticky is pushes  the page to
the right side, adjusting the body-margin.

* Resolves: `#45048 <http://forge.typo3.org/issues/45048>`_
* Commit: `81af92c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=81af92c5e36fc5e0b38514ce41ceb4890a145f7d>`_

[TASK] Make page tree narrower
-----------------------------------------------------------------------------------------

* Resolves: `#45277 <http://forge.typo3.org/issues/45277>`_
* Commit: `ba16f39 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=ba16f39be87ec32d450ab68e40c033eb7f6b0791>`_

[TASK] Change plus/minus with play/pause icons in packages module
-----------------------------------------------------------------------------------------

* Commit: `a3830b1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a3830b1afcacab336049dd236b2d24dd977d59b3>`_

[TASK] Fix rendering date in documentation index
-----------------------------------------------------------------------------------------

The change in I2d71b575b0a16a095aca20f1ffddf802ad426ebc did nest markup,
so the "today" was not expanded.

* Commit: `dc96517 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=dc96517d76baa69bda2aca4d73ccc355f5ed401c>`_

[TASK] Add rendering date to documentation index
-----------------------------------------------------------------------------------------

* Commit: `e695f7f <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=e695f7f6c16d4413f76ad55134d2832fbb2c17a5>`_

[BUGFIX] Fix error on server communication
-----------------------------------------------------------------------------------------

The change for replacing the notification library
introduced a regression because the storage plugin
depends on Midgard.Notifications.
This change introduces a wrapper to output those
notifications to console in development context.

For later cleanup an issue has been filed on forge:
http://forge.typo3.org/issues/45049

* Commit: `4a54ac0 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4a54ac01601cb7157c1843e7b4b5b66086da53f0>`_

[TASK] Syncronization between Inspector, Content, Pagetree and Inspectree
-----------------------------------------------------------------------------------------

Pagetitle:
Pagetree -> Inspector, Inspectree
Inspector -> Pagetree, Inspectree

Hidden
Inspector -> Pagetree, (Inspectree)

Synchronizations depends on popover status

Patch also includes a fix that the timed Visibility
of a page is shown in the Pagetree in general

* Resolves: `#41875 <http://forge.typo3.org/issues/41875>`_
* Commit: `944c5f1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=944c5f180b6dd420028f80d6e097b71a4c0b5b5d>`_

[BUGFIX] Fix overflow and height of inspector panel
-----------------------------------------------------------------------------------------

* Commit: `110e1ad <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=110e1ad3b5e118f590a9cb66c5da87c08b60e014>`_

[BUGFIX] Hide inspector apply button when in preview mode
-----------------------------------------------------------------------------------------

* Commit: `a906f4b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a906f4b54d456e49871ae7e9489357530edca51c>`_

[BUGFIX] Add default modal style to confirm dialog
-----------------------------------------------------------------------------------------

Add the default modal styling from backend module
modal dialog.

* Fixes: `#44582 <http://forge.typo3.org/issues/44582>`_
* Commit: `a91d910 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a91d91045d64c8328c3550f67b6533318ee16544>`_

[TASK] Make button styling consistent
-----------------------------------------------------------------------------------------

* Commit: `fa0de24 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=fa0de247daf4ff57105cfc40908883c733034486>`_

[BUGFIX] Fix uncatched javascript error
-----------------------------------------------------------------------------------------

When you select a image content element the image preview
is loaded from the server. If the ajax call callback method
is called after deselecting the content element this
results in an uncatched error as the preview image would
be added to an ember view in the inspector that does not
exist anymore.
Besides that this change cleans up unused popover DOM
elements on AJAX page reload.

* Commit: `58e10fc <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=58e10fc9c7143c95ec500a6e84c11bd1db857e03>`_

[BUGFIX] Fix JavaScript build regression
-----------------------------------------------------------------------------------------

this build regression has been introduced by I43468193c07fe01a8e53d0f21cd5c574ec9b6b56

* Commit: `d797b5e <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=d797b5e0be786819e40445436b3c5a95cc99b191>`_

[BUGFIX] Add route for wireframe rendering
-----------------------------------------------------------------------------------------

There needs to be a route for wireframe rendering otherwise
plugins with links will produce an exception in wireframe
mode.
The added route is a dummy route to create a URI, the result
will then match the normal frontend routes.

* Commit: `68d6cc4 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=68d6cc447ee95989107a483a155355ed86144759>`_

[TASK] Remove green color of indicator icon in toolbar
-----------------------------------------------------------------------------------------

* Commit: `118b164 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=118b164e19b3504a2a6848d2ad5201ef10cae5e3>`_

[TASK] Replace notification library
-----------------------------------------------------------------------------------------

The used notification library has been replaced.

"Ok" and "Notice" are automaticly hidden after a timeout period

"Notice" and "Warning" are not hidden after a timeout

Readme file updated with information about library

* Commit: `3202bee <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=3202beeb0fc52a37cdd43e3f87ad90f0e4582b36>`_

[TASK] Improve caching and loading of VIE and node type schemata
-----------------------------------------------------------------------------------------

This change implements a client-side resource cache that uses the
session storage and allows for early preloading of resources before
the page is loaded which speeds up the UI loading process.

Additionally the Neos node type schema is not embedded into the markup
but also loaded via the same mechanism as the VIE schema.

* Resolves: `#44976 <http://forge.typo3.org/issues/44976>`_
* Commit: `1b2f0d9 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1b2f0d9794958cc3ca2f5f42c72579816e6f6d55>`_

[FEATURE] Make inspector header stick to the top
-----------------------------------------------------------------------------------------

* Commit: `10c19cb <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=10c19cb46ef6a211d37a48e61c62e4dc836be5b6>`_

[BUGFIX] Reload all top level t3-reloadable-content elements
-----------------------------------------------------------------------------------------

Currently only the first found t3-reloadable-content is really
relaoded. The change will reload all top level elements again.

* Commit: `2add31d <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=2add31d17d35733454ce2ef614564c042898d974>`_

[BUGFIX] Fix paragraph line-height for modules
-----------------------------------------------------------------------------------------

* Commit: `40d1d43 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=40d1d435380c2a593b0e485ef2ded5cf2f6ca26d>`_

[FEATURE] Add cancel button to inspector
-----------------------------------------------------------------------------------------

* Commit: `fdb2509 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=fdb250975ebbfd7c20d3e1b4b5e464dcd59dccd8>`_

[!!!][TASK] Make pagetree permanently visible
-----------------------------------------------------------------------------------------

Add a fold out left tree panel for the page tree.
Moves the code for the page tree into a separate
file.

* Resolves: `#44994 <http://forge.typo3.org/issues/44994>`_
* Commit: `94efd42 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=94efd42e84f029b308db24f760f34d64f85c1483>`_

[FEATURE] Add styling of Hallo dropdown menus
-----------------------------------------------------------------------------------------

* Commit: `c3a0600 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=c3a0600237afc739d9e4cde5bf944c59d69fc283>`_

[BUGFIX] Fix field name for domain model Postgre migration
-----------------------------------------------------------------------------------------

* Commit: `b1c5c9a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=b1c5c9a8fd887ce08d8c07c1862347982d93097a>`_

[BUGFIX] Make section handles visibility respect removed elements
-----------------------------------------------------------------------------------------

This also fixes the issue that the logic if it should be shown or not
only happened when the handle was initialize and not when the vie
collection changed. This means the handle will appear after removing
all the content and disappear again when a content element is added.

* Commit: `e4dd10c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=e4dd10ca0585643e5c6bb1b62648c9ad8cd9aba8>`_

[BUGFIX] Prevent recursive node selection inefficiency
-----------------------------------------------------------------------------------------

* Commit: `d06d53c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=d06d53c251c47a9c302aee66cb9b7b29bedb1408>`_

[BUGFIX] Fix fetching the node before redirect
-----------------------------------------------------------------------------------------

When a user is redirected to the root page of a site
the call to substr() returned FALSE. This returned
in a 500 server error where getNode() expects a
string and no boolean. This change passes / to
getNode() in this case.

* Commit: `1c8c320 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1c8c320a626e73c92c5cfd07ec8bf0fe43e625b1>`_

[TASK] Import all backend CSS files for optimization
-----------------------------------------------------------------------------------------

This file should be optimized using Jenkins with the r.js optimizer.

* Commit: `6b9ceff <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=6b9ceff9f6bd6ffa90a4374e37dccaca45019c95>`_

[BUGFIX] Persist original image in XML import
-----------------------------------------------------------------------------------------

This is necessary when trying to edit the image
element, since we need the original image for
cropping/resizing.

* Commit: `f07d752 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f07d752866007f6f466a21568a010ae1ba59bdef>`_

[TASK] Various styling improvements
-----------------------------------------------------------------------------------------

* Removes rounded borders for menus, widgets, buttons, modals etc.
* Replaces blue outline on focussed fields with orange

* Commit: `964f1d7 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=964f1d768871d981eb8fcc818fcb38f04147fdbf>`_

[FEATURE] Allow HTML5 properties for Ember fields
-----------------------------------------------------------------------------------------

* Commit: `0d7fc67 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=0d7fc6752add6c45596e9a6644e7935a51b1bac1>`_

[TASK] Clean up in JavaScript and handlebar templates
-----------------------------------------------------------------------------------------

* Commit: `5bfc53b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5bfc53b1c853fe908e210e7354deb248ae6eaf82>`_

[FEATURE] Add hide/unhide button to content handles
-----------------------------------------------------------------------------------------

* Commit: `98556b6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=98556b67b043187368e7ba9e8c8bed7283d30c52>`_

[BUGFIX] Don't show loading indicator when paste throws error
-----------------------------------------------------------------------------------------

* Commit: `a46ac8c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a46ac8c64eb5280b870161f018df6e5b891eb0f6>`_

[TASK] Change minimum height of content element
-----------------------------------------------------------------------------------------

* Commit: `7479884 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=74798844a81b332f0af014d652e0dd361533a44e>`_

[BUGFIX] Fix creation of content in empty section
-----------------------------------------------------------------------------------------

This issue occurs when a section only contains removed content.
When creating a new content element from the sections content
handles, it uses the last node as reference. Since this content
element is removed the node object converter will throw an error.

* Commit: `7b86853 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=7b868539986e5cec88d39ec05e0300deb8642690>`_

[FEATURE] Add current request to TypoScript context
-----------------------------------------------------------------------------------------

* Resolves: `#44958 <http://forge.typo3.org/issues/44958>`_
* Commit: `078a30e <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=078a30ef321bbdb3e0ac9f751d79c1ee173592bd>`_

[FEATURE] Top level case to decide rendering path
-----------------------------------------------------------------------------------------

To allow rendering of different output formats a top
level Case is introduced that renders "page" by default.
That way it is easy to hook in and add other types of output.

* Resolves: `#44949 <http://forge.typo3.org/issues/44949>`_
* Related: `#44948 <http://forge.typo3.org/issues/44948>`_

* Commit: `5598bb2 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5598bb295698c0c620fa7e411049a5e730f6b733>`_

[BUGFIX] Remove duplicate _removed value from content wrapping
-----------------------------------------------------------------------------------------

* Commit: `fb09411 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=fb09411abacf0dd48c62dcdf4c8dbe497788ace5>`_

[BUGFIX] Clear inspector after deleting an element
-----------------------------------------------------------------------------------------

* Commit: `d61e911 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=d61e911e003da5bc8897d6a02bac0b02e7be7dbf>`_

[BUGFIX] Prevent the node object converter mapping null to target type
-----------------------------------------------------------------------------------------

* Fixes: `#42415 <http://forge.typo3.org/issues/42415>`_
* Commit: `6a82fe0 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=6a82fe081c368672246cef61cbfeb13d141fa5cc>`_

[!!!][FEATURE] Add a site management module
-----------------------------------------------------------------------------------------

!!! Requires database schema update

* Adds site management module where it is possible to create,
  update and delete both sites and domains.
* Adds a hostname validator
* Adds a unique entity validator
* Adds a node name validator
* Adds a package key validator
* HostPattern marked as identity in Domain model
* Resolves: `#40325 <http://forge.typo3.org/issues/40325>`_

* Commit: `711d5cd <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=711d5cd64823a81cfb46b4102602d717fce44322>`_

[BUGFIX] Prevent JavaScript error on exception from Backbone sync
-----------------------------------------------------------------------------------------

* Commit: `342b76b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=342b76b26a61530cdee100217fced9261aae6192>`_

[BUGFIX] Remove t3-button class from dialog close button
-----------------------------------------------------------------------------------------

* Commit: `759f58a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=759f58af03c87e0ddbb9e1950460846912db242b>`_

[BUGFIX] Fix typo in comment for neos/content/ui.js
-----------------------------------------------------------------------------------------

* Commit: `43cefac <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=43cefac4a0b15b36f0ddaa6c5e03b0720a19dc6d>`_

[TASK] Tweak figures used in documentation
-----------------------------------------------------------------------------------------

Tweaks image inclusions in the sources and fixes some tiny markup
errors along the way.

* Related: `#44885 <http://forge.typo3.org/issues/44885>`_
* Commit: `f7153bc <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f7153bc3f59596675e85e870c537f2fe3621dec8>`_

[BUGFIX] Create site kickstarter object for site import step
-----------------------------------------------------------------------------------------

* Commit: `b1ef7d2 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=b1ef7d26817519e677182abce635228c89f282ca>`_

[TASK] Remove .orig version of packages controller
-----------------------------------------------------------------------------------------

* Commit: `4d2c2b7 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4d2c2b72e3de5f75f4678db80ecabd8187245a5f>`_

[TASK] Remove superfluous $securityContext
-----------------------------------------------------------------------------------------

The $securityContext member of the RoutingLoggingAspect was not used
in the code.

* Commit: `4c75263 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4c75263c3ef1a2d61d1fa27a779c1eb4eb79a826>`_

[TASK] Adjust reST documentation for docs.typo3.org
-----------------------------------------------------------------------------------------

Moves images around, adjust sources as needed, add Settings.yml.

Tweaked the way TOCs are laid out.

* Related: `#44885 <http://forge.typo3.org/issues/44885>`_
* Commit: `a5929ad <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a5929adc09cf5d8ed1798b5e8258bc53130d58fb>`_

[FEATURE] Make non-editable-overlay configurable through content type schema
-----------------------------------------------------------------------------------------

For testing, the corresponding change for TYPO3.Neos.ContentTypes is
needed.

* Resolves: `#44812 <http://forge.typo3.org/issues/44812>`_
* Commit: `e4cb3c0 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=e4cb3c073f5fcd81748f06322e83bdb760683f25>`_

[TASK] Make TYPO3.SiteKickstarter a dev dependency
-----------------------------------------------------------------------------------------

This commit is a replacement for 8eaedb which had to be reverted
because of Neos setup wizard depending on the SiteKickstarter.
Now the import step will check if the SiteKickstarter package
is activated, and if not it will show a notification.

The setup will still finish.

* Commit: `fb21ec8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=fb21ec853dc14d1bf42bcb8c7b898feb2eff4ddb>`_

Revert "[TASK] Make SiteKickstarter a dev dependency"
-----------------------------------------------------------------------------------------

Neos actually depends on this package, so we should revert this change till a  better solution is found.

This reverts commit 8eaedb11d73adb355cf661c154c6b2c29560796a

* Commit: `2d9fc25 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=2d9fc25a322c4c2aed8a995c0ffd135a23415e40>`_

[TASK] Make SiteKickstarter a dev dependency
-----------------------------------------------------------------------------------------

This change makes the TYPO3.SiteKickstarter a dev dependency of
TYPO3.Neos so it's only installed with composer install --dev.

* Commit: `8eaedb1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=8eaedb11d73adb355cf661c154c6b2c29560796a>`_

[BUGFIX] Show image upload errors and disable upload for wrong types
-----------------------------------------------------------------------------------------

When selecting images that do not match the accepted filetype of the
uploader an error is shown and the upload button is disabled.

Additionally the image extensions "jpeg" and "gif" are supported.

* Resolves: `#44683 <http://forge.typo3.org/issues/44683>`_
* Commit: `076b0c0 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=076b0c05d4b5e36194285838335260a8c02bd540>`_

[BUGFIX] The contentTypeSelectorTabs is not fully visible
-----------------------------------------------------------------------------------------

When contentTypeSelectorTabs is open the right t3-inspector
is placed on top. This results in that the content
types are not fully visible

Due to a position absolute setting in jquery popup the
z-index is set to 10001. The setting is found in
jquery.popover.js on line 282.

* Fixes: `#44667 <http://forge.typo3.org/issues/44667>`_
* Commit: `75c8d95 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=75c8d955a638c25d1444616a14a34c2ba7135f15>`_

[BUGFIX] domain matching must work if given hostname is shorter than a domain
-----------------------------------------------------------------------------------------

An "undefined array index" error occured if my hostname e.g. was
"foo.bar", and there was a domain record configured for "some.foo.bar".

This change adds a testcase for this and fixes the error.

* Commit: `c2518c6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=c2518c63a5cdb2e7312982f47d2b39c36591144c>`_

[BUGFIX] Fix empty/boolean labels of search results
-----------------------------------------------------------------------------------------

When searching using the toolbar some result shows the
node label as a boolean. Instead it should use the node's
label generated with a label generator.

* Fixes: `#44304 <http://forge.typo3.org/issues/44304>`_
* Commit: `1d1c2c7 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1d1c2c7c42967f07322fcade8feb99a99fccb4da>`_

[BUGFIX] document errors with position:relative on body
-----------------------------------------------------------------------------------------

* Commit: `f3e2856 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f3e2856b20467eb47101d30946e4574b84f03723>`_

[FEATURE] Support manually set target node for Shortcut
-----------------------------------------------------------------------------------------

This enhances the Shortcut content type to support a specifically set
target node which overrides the default behavior or redirecting to the
first sub node. The property "targetNode" currently needs to be set
manually (through the Node API) as there is no user interface in place
yet.

* Resolves: `#44403 <http://forge.typo3.org/issues/44403>`_
* Commit: `cd80e4c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=cd80e4ce6c644d0a700b39e7b2593a81d3ddc442>`_

[TASK] Improved error handling for missing root TypoScript template
-----------------------------------------------------------------------------------------

If a site does not contain a root TypoScript template (or it was placed
at a wrong location), a meaningful error message is now displayed.

* Resolves: `#44404 <http://forge.typo3.org/issues/44404>`_
* Commit: `485ba1a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=485ba1aca1a2500d12c6ba5364f185b9c6059ddc>`_

[BUGFIX] Fix height of inspector header due to green border
-----------------------------------------------------------------------------------------

* Commit: `1e22439 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1e224392372ad5b82b1421cc3ad7969f8e32718f>`_

[TASK] Remove rounded borders from various elements
-----------------------------------------------------------------------------------------

* Removes rounded borders from following popovers:
  new content element, page tree, inspect tree
* Removes rounded corners for the content element handles
* Adds the possibility to add additional classes
  to the popover root element for popover buttons

* Commit: `a21eef8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a21eef8fce29e560ddc9b72b793aac725ce8e9a3>`_

[BUGFIX] New content element popover is positioned fixed
-----------------------------------------------------------------------------------------

* Fixes: `#44420 <http://forge.typo3.org/issues/44420>`_
* Commit: `c98812f <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=c98812f9a9931f597878d8bd74416067c5c48ead>`_

[BUGFIX] Remove popover doesn't close new content element popover
-----------------------------------------------------------------------------------------

* Fixes: `#44419 <http://forge.typo3.org/issues/44419>`_
* Commit: `768dc21 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=768dc214515622662da95e4ac2211948b2290a5e>`_

[TASK] Upgrade Font Awesome to v3.0
-----------------------------------------------------------------------------------------

This release includes a new icon font with remade
icons from scratch to support 14px rendering.

* Related: `#41009 <http://forge.typo3.org/issues/41009>`_
* Commit: `1803587 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=18035875d8c204fb9ec2365c401f0a7c017f6b45>`_

[BUGFIX] Allow user dropdown to overflow top bar
-----------------------------------------------------------------------------------------

Change I30881c3e89b4c1062fde6abe3181670860a06297 added
overflow hidden to t3-ui-top, but it should only be
applied in preview mode.

* Commit: `6ae42f4 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=6ae42f4a3733b7ddf67b72234a7bc7c84985cca0>`_

[BUGFIX] Make clicking on new content element icons possible
-----------------------------------------------------------------------------------------

Inside the new-content-element popover, clicking on the icon
of the content type did a redirect to "#", losing the current
page and showing the live workspace instead of adding a new
content element.

This change fixes this and makes the link easier to click and
fixing some styling issues as well as removing the non-used
HTML attributes as well.

Also removes the depreacted new content element template.

* Commit: `4f3994c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4f3994c39d5ef1bf3a12c4a481b52ce7ebb3bf5b>`_

[BUGFIX] Fix transitions when toggling preview mode
-----------------------------------------------------------------------------------------

* Add transition for inspector panel
* Fix overflow for top panel
* Remove margin-right on body (normalize)
* Commit: `24ee3ca <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=24ee3caed5e6ae8cff135aabef14dba4252a789d>`_

[BUGFIX] Remove use of updateSchema in package management
-----------------------------------------------------------------------------------------

* Fixes: `#44409 <http://forge.typo3.org/issues/44409>`_
* Commit: `28650fe <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=28650feedfdefb6c49204a7454c7960af127b7fb>`_

[TASK] Additional check for date properties
-----------------------------------------------------------------------------------------

This adds another check to make sure that corrupt or unexpected values
of date node properties don't lead to a fatal error caused by calling
methods on a non-object.

* Resolves: `#44400 <http://forge.typo3.org/issues/44400>`_
* Commit: `73ebcb5 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=73ebcb5e6b16878359bd856b732d4e4e04d7e9d8>`_

[TASK] Disable open popover windows when entering preview mode
-----------------------------------------------------------------------------------------

Disables the open popover windows when clicking the on the
preview button.

* Fixes: `#42208 <http://forge.typo3.org/issues/42208>`_
* Commit: `a020339 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a020339fdc9a2c7da7210cabf76d5ce78a2faa54>`_

[BUGFIX] Remove margin when activating search toolbar
-----------------------------------------------------------------------------------------

Bug introduced in task #41849 creating normalized css

* Fixes: `#44211 <http://forge.typo3.org/issues/44211>`_
* Commit: `94b0eb7 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=94b0eb7cf07019666e408aac8f82ef322893fc11>`_

[BUGFIX] Make the search toolbar searchable again
-----------------------------------------------------------------------------------------

* Fixes: `#44220 <http://forge.typo3.org/issues/44220>`_
* Commit: `3597306 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=359730618ad788b7db17a38a8a4314c79c94f0f3>`_

[BUGFIX] Fix typo in variable name for LauncherController
-----------------------------------------------------------------------------------------

* Fixes: `#44212 <http://forge.typo3.org/issues/44212>`_
* Commit: `d44c0a9 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=d44c0a914d70b4751d1012d3495aab514d7a14ad>`_

[TASK] Add subpage removal hint to delete prompt
-----------------------------------------------------------------------------------------

When deleting a page from the page tree the modal prompt description
now shows that subpages will be removed as well (if present).

Some cleanup is done along the way.

* Resolves: `#44035 <http://forge.typo3.org/issues/44035>`_
* Commit: `403a1e6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=403a1e6ed78bd2d879de4a9ef8a58c182e42fecb>`_

[TASK] Redirect to last edit page after logout
-----------------------------------------------------------------------------------------

Uses the Neos_lastVisitedUri in a similar way to the login.

* Resolves: `#40304 <http://forge.typo3.org/issues/40304>`_
* Commit: `34eb9ae <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=34eb9ae0928c4b6ecc28e55da72a9e8fc8b80657>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos.ContentTypes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Use bundled file for backend CSS
-----------------------------------------------------------------------------------------

This change can be applied after I015035ac40e112e060fd7343d33d28674a707649.

* Commit: `e566166 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=e5661665d7033e869fc09ff887ebaeb9f4defe1a>`_

[FEATURE] Make non-editable-overlay configurable through content type schema
-----------------------------------------------------------------------------------------

You also need the corresponding change in TYPO3 Neos for this.

* Resolves: `#44812 <http://forge.typo3.org/issues/44812>`_
* Commit: `527287a <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=527287a73621ab801aa849b6af5c1bf7f1e330a9>`_

[BUGFIX] Remove default title property from content object
-----------------------------------------------------------------------------------------

* Commit: `efe4269 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=efe4269004c4eebcf2f2a680a71bbbe51707e5b4>`_

[TASK] Section rendering should use getNodePath in collection again
-----------------------------------------------------------------------------------------

Cleanup after the previous changes to make overrides of
Section.Default easier again.

* Commit: `d9bd0e4 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=d9bd0e4d28040c0d33d894462763e729bb18a098>`_

[BUGFIX] Fix section rendering in backend context
-----------------------------------------------------------------------------------------

This is a hot fix for broken section rendering in the backend user
interface introduced in https://review.typo3.org/#/c/17430/

* Commit: `09e8ba3 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=09e8ba361acaa442077a125c41d8fe602404e7f0>`_

[FEATURE] Support inline rendering of custom Folder content types
-----------------------------------------------------------------------------------------

Previously the "Section" content type's TypoScript object was told to
render the collection of content elements of itself. In order to render
custom page-like types (inheriting from Folder) inline (sic!) into a
page, the Section TypoScript object is now a Case object. Its fallback
case results in the same behavior as before the refactoring.

This way it is possible for other packages (or by Neos at some point)
to pass control to a specialized TypoScript for specific node types.

More specifically: This allows a Blog Post TypoScript object to render
a Blog Post node (inheriting from Folder) inside a page template.

* Resolves: `#44406 <http://forge.typo3.org/issues/44406>`_
* Commit: `adfd203 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=adfd20345c2c0cdc946df46cc49d901e68264590>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TYPO3CR
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Show meaningful error message on invalid node path
-----------------------------------------------------------------------------------------

If some code passes an invalid path (for example NULL) to getNode(), due
to some bug, Node will now throw a meaningful exception.

* Commit: `34cdf80 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=34cdf802759f7894f008c87f590d9f054f0fec82>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TypoScript
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[FEATURE] Allow setting absolute path in matcher
-----------------------------------------------------------------------------------------

With the new property renderPath in Matchers it is possible to
set a TypoScript path and use the configuration for rendering
instead of giving a prototype name.

* Resolves: `#44948 <http://forge.typo3.org/issues/44948>`_
* Commit: `0a3daa4 <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=0a3daa49f8f7642a9ace3405aa93c86e72e53d26>`_

[BUGFIX] Add a check for unsetted Paths in Arrays
-----------------------------------------------------------------------------------------

When a path is unset it isn't really unset by the TypoScript
Parser, instead it's set to NULL. This can throw an error
of non existing paths that TypoScript tries to render.
As a temporary Fix this changeset adds a check if the
path is NULL to skip it.

* Resolves: `#44902 <http://forge.typo3.org/issues/44902>`_
* Commit: `978db1f <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=978db1f2a88ce57bd51596fe62bda3de23f7c261>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Aloha
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Eel
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Fizzle should make boolean comparison
-----------------------------------------------------------------------------------------

Using unquoted 'true', 'false' literals should result in a
comparison of boolean values not of strings. This change also adds
support for numeric values in filters.

* Commit: `ac4b36c <http://git.typo3.org/FLOW3/Packages/TYPO3.Eel.git?a=commit;h=ac4b36c3856c5c88d9ab6150aec27cd17a618c2f>`_

[TASK] Parser template should work with PSR-0 paths
-----------------------------------------------------------------------------------------

Fixes include path in AbstractParser PEG template and a few
naming and whitespace issues.

* Commit: `9e52727 <http://git.typo3.org/FLOW3/Packages/TYPO3.Eel.git?a=commit;h=9e5272794cfba86d9c70aa4a8f9b9f8042f659e1>`_

[TASK] Parser generation script should use PSR-0 paths
-----------------------------------------------------------------------------------------

* Commit: `800d1d6 <http://git.typo3.org/FLOW3/Packages/TYPO3.Eel.git?a=commit;h=800d1d65a54827adcc08e498dea56e7dbfae6390>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.ExtJS
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Form
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Fix FileTypeValidator
-----------------------------------------------------------------------------------------

Adds the required $supportedOptions field to the
FileTypeValidator.

* Commit: `05be573 <http://git.typo3.org/FLOW3/Packages/TYPO3.Form.git?a=commit;h=05be573ad81729e7c0798b97df75643671d8038e>`_

[FEATURE] Provide a better Exception for PropertyMapping
-----------------------------------------------------------------------------------------

If the PropertyMapper fails to map some properties it
can be hard to understand what happened, because
the PropertyMapper doesn't get the whole PropertyPath
from the ProcessingRules.
To better understand what happens this change catches
the Property\\Exception and throws a new Exception
with the propertyPath that was tried to be mapped.

Example:
http://dl.dropbox.com/u/314491/Screenshots/09.png

Without this Change only the 2 nested Exceptions
would have been thrown.

* Commit: `0ecc0b7 <http://git.typo3.org/FLOW3/Packages/TYPO3.Form.git?a=commit;h=0ecc0b747d101917a67f832cc5c7bf1f74ec4fe5>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Imagine
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Media
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Setup
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Fix indentation of flash messages in login action
-----------------------------------------------------------------------------------------

* Commit: `ea9b8b4 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=ea9b8b4a0cd612600d1fb6fdf002fe95bcc86699>`_

[TASK] Logout after finalized setup & improve hidden removal
-----------------------------------------------------------------------------------------

This is needed for being able to login into the neos backend
properly after a finalized setup.

* Commit: `6ea7acd <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=6ea7acda2ff5acc83bfbae7a788a51302de596e9>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.SiteKickstarter
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No changes

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Twitter.Bootstrap
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Upgrade bootstrap version to 2.2.2
-----------------------------------------------------------------------------------------

* Commit: `360bbd6 <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=360bbd60c85dada87a204a9c6a9e1197441867ba>`_

[FEATURE] Add FlashMessage-ViewHelper
-----------------------------------------------------------------------------------------

Needed since the ViewHelper that ships with Fluid does not render the HTML in
the needed structure and with the needed class names to be used with the CSS
from Bootstrap.

* Resolves: `#43065 <http://forge.typo3.org/issues/43065>`_
* Commit: `8288371 <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=8288371b84d1c24579e055122028c5be9b3e62a6>`_

