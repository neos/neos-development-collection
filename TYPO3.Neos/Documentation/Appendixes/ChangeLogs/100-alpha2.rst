=========================
1.0.0-alpha2 (2012-12-20)
=========================

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Base Distribution
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

* Related: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `462f5b4 <http://git.typo3.org/TYPO3v5/Distributions/Base.git?a=commit;h=462f5b4b732ac793b61e1c768622113c6df39859>`_

[TASK] Rename Vendor to Libraries and update composer-name
-----------------------------------------------------------------------------------------

* Related: `#42013 <http://forge.typo3.org/issues/42013>`_
* Commit: `d80c86a <http://git.typo3.org/TYPO3v5/Distributions/Base.git?a=commit;h=d80c86a2e237b98414932222b209510b91ae3472>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Make hover & active state orange in dropdown menus
-----------------------------------------------------------------------------------------

* Commit: `4debc69 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4debc69aa0e6b0e7fc8704375389fdbd04926eec>`_

[TASK] Remove standard error message from Module/StandardController
-----------------------------------------------------------------------------------------

This removes the standard error message and leaves only the list
of validation errors in place.

* Resolves: `#42211 <http://forge.typo3.org/issues/42211>`_
* Commit: `2e9d4c0 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=2e9d4c0b414045c8690837442970129bbe919faa>`_

[BUGFIX] Add page title to delete prompt
-----------------------------------------------------------------------------------------

Add the page title to the delete prompt when trying to delete a page
from the page tree.

* Related: `#42769 <http://forge.typo3.org/issues/42769>`_
* Commit: `0170718 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=017071845835601f8f366db289ca918a1e15d042>`_

[BUGFIX] Add action parameter to back link in create user
-----------------------------------------------------------------------------------------

* When trying to create a new user in the Administration part  and
  clicking on new user. A 500 error is thrown because of missing
  action paramter.
* Add a action parameter to the back link in new.html template.
* Fixes: `#44036 <http://forge.typo3.org/issues/44036>`_

* Commit: `feb7df5 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=feb7df530dadc8c64d7d6c0c0bc4330e54a807e7>`_

[BUGFIX] Adjust code & template to recent Fluid changes
-----------------------------------------------------------------------------------------

With Ifa4ccaafb550526ec977d93059ca123b18ef5462 one of
the arguments "action" and "actionUri" is required.

This change adjusts the Edit template of the User administration
module accordingly

* Related: `#43589 <http://forge.typo3.org/issues/43589>`_
* Commit: `07d3a25 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=07d3a25c5d70b11c28ab2b28f8f3f5e96a550979>`_

[TASK] Remove "Import package" from package manager
-----------------------------------------------------------------------------------------

As there is no support for importing packages after composer migration
the functionality is removed from the "Package Management" module

* Resolves: `#43829 <http://forge.typo3.org/issues/43829>`_
* Commit: `8c0da8a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=8c0da8a2ca960d62e9a7a2243052a86b4cfc1077>`_

[TASK] Removed testable HTTP flag from tests
-----------------------------------------------------------------------------------------

Testable HTTP is now always enabled, thus the flag to switch it on can
be removed.

* Related: `#43590 <http://forge.typo3.org/issues/43590>`_
* Commit: `1633dd6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1633dd640365b2ad027faf3748d23bdb589a96e2>`_

[TASK] Removing alert when HTML selector wasn't found
-----------------------------------------------------------------------------------------

When a page, a user visits, has a different html structure as the page
before it has to be fully reloaded by 'window.location.href = uri;'. So
nothing really went wrong as it was announced.

Especially after creating a new page the HTML structure is not the
same. So the user would have to handle an alert every time although
nothing went wrong.

* Resolves: `#43479 <http://forge.typo3.org/issues/43479>`_
* Commit: `754f79a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=754f79a14fc3df6a4cdb4529d97573dc62d15871>`_

[TASK] update VIE to latest Master
-----------------------------------------------------------------------------------------

This fixes a bug for which some content elements disappeared in the
"new content element" popover if there was a wrong loading order.

See https://github.com/bergie/VIE/pull/144 for details of the change
in VIE.

* Commit: `14799a2 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=14799a269e2d0bc9b15914714b006b7405c6e902>`_

[BUGFIX] Load minified JavaScript by default
-----------------------------------------------------------------------------------------

In change I76121a9bc8a4eda8dc3120155d1cb7b3ddef9cf1 where Phoenix
was renamed to Neos, the loading of minified JavaScript was disabled
by accident. This change fixes this.

* Related: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `bc9470d <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=bc9470db5cfd8864d4ac8a75ed142424c7b51b59>`_

[TASK] JavaScript code formatting and refactoring
-----------------------------------------------------------------------------------------

Extract some local variables and fix formatting glitches.

* Commit: `67c2070 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=67c20700a619548f58b195a935c6540dab163442>`_

[BUGFIX] Adjust Neos to changed resource ViewHelper in Fluid
-----------------------------------------------------------------------------------------

The resource ViewHelper no longer has a uri property so Neos
uses path now in the module widget.

* Fixes: `#43306 <http://forge.typo3.org/issues/43306>`_
* Commit: `a2a88c6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a2a88c6a0acd944b4feffe95e6cd1ae76291ee83>`_

[FEATURE] Allow additional CSS and JavaScript in Backend Modules
-----------------------------------------------------------------------------------------

This allows to configure the inclusion of multiple additional
CSS or JavaScript files from any desired package, for the
use in Backend Modules. Such a configuration would be for example::

	TYPO3:
	  Neos:
	    modules:
	      administration:
	        submodules:
	          sample:
	            label: 'An example module'
	            controller: 'Acme\\Foobar\\Controller\\Module\\Administration\\SampleController'
	            description: >
	              This is just a description for the controller.
	              Note the additional "resources.css|js" directives.
	            icon: 'resource://TYPO3.Neos/Public/Images/Icons/Black/notepad_icon-24.png'
	            additionalResources:
	              styleSheets:
	                - resource://Acme.Foobar/Public/Css/Module/Sample.css
	              javaScripts:
	                - resource://Acme.Foobar/Public/JavaScript/Module/Foo.js
	                - resource://Acme.Foobar/Public/JavaScript/Module/Bar.js

Besides, it removes the obsolete ``type`` argument at the
``<link rel="stylesheet" ...`` tag.

* Resolves: `#43156 <http://forge.typo3.org/issues/43156>`_
* Commit: `bc656b2 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=bc656b2a91753ecf946559a152cff926c90bb73a>`_

[BUGFIX] Content Module: Fixed several minor UI bugs
-----------------------------------------------------------------------------------------

* Add option to add a class for popover elements
* Fixed JS bug on popover setting for adding an ID
* Add class for new contentelement popover
* Fixed styling on new content element popover
* Fixed styling on save indicator
* Optimized position of content element handles

* Commit: `df1c627 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=df1c627910303bde78ed0884a9d600c3a2e74906>`_

[TASK] Add notices for skipping steps in setup
-----------------------------------------------------------------------------------------

Needs Ia242184567be52e39c97d4b641706ed8e3423577 to display the
tooltip on the skip button.

* Resolves: `#42209 <http://forge.typo3.org/issues/42209>`_
* Commit: `ce3b377 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=ce3b377bc8ceabec0b689ec77854a56e0c68ab5a>`_

[TASK] Clean up LoginController
-----------------------------------------------------------------------------------------

Remove no longer needed ExtDirect actions.

* Commit: `05cb76c <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=05cb76c4fb5d67ae241a232bea5d0005f0fccd0f>`_

[TASK] Adjust validators to new option handling
-----------------------------------------------------------------------------------------

The validators have been changed and now need to declare their supported
options. This change adjusts the AccountExists and Password validators.

The change to validator behavior in TYPO3 Flow was
I2b32130840892417214cf50cad772190fc2576c0.

* Related: `#37820 <http://forge.typo3.org/issues/37820>`_
* Commit: `5e2e961 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5e2e961272dff76d585f3c21e509870abf92b5da>`_

[TASK] After creating a page that page is now selected
-----------------------------------------------------------------------------------------

It is also now possible to create two pages descendant without breaking
the tree.

* Resolves: `#41356 <http://forge.typo3.org/issues/41356>`_
* Resolves: `#42670 <http://forge.typo3.org/issues/42670>`_

* Commit: `214c812 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=214c81232fc77ba15faa4859e5247bce59e4e961>`_

[BUGFIX] Use buttons in content element handles for actions
-----------------------------------------------------------------------------------------

* Related: `#42014 <http://forge.typo3.org/issues/42014>`_
* Commit: `f90106d <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f90106df6627958f62eea5d1a3c4c691a5f4f79e>`_

[BUGFIX] Add missing dependencies for Ember and createjs
-----------------------------------------------------------------------------------------

* Commit: `5e9f91f <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5e9f91fff2814801d2d1ff538530107a65589df4>`_

[TASK] Small code cleanup in ext direct service node view
-----------------------------------------------------------------------------------------

* Commit: `574731f <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=574731fb20d0d279231bb99e4eb681bdd278cab8>`_

[TASK] Move focus to the title field when creating a page
-----------------------------------------------------------------------------------------

* Resolves: `#41357 <http://forge.typo3.org/issues/41357>`_
* Commit: `80c1605 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=80c160511113b9cc8dabbeaeddeacc3e9b3690cb>`_

[TASK] Remove remains of the deletion drop zone
-----------------------------------------------------------------------------------------

* Commit: `ce6ba02 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=ce6ba029daf4a48973d69317b42d77aa25352473>`_

[BUGFIX] Fix page reload after clicking on links
-----------------------------------------------------------------------------------------

Caused by introducing section elements with inline reloadable
content (I48055d6bfba7cb83173ba336536ded6433965007)

* Fixes: `#42410 <http://forge.typo3.org/issues/42410>`_
* Commit: `5cdd176 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5cdd176c7e6318e2b67f83e195cb5382fdea6d64>`_

[TASK] Remove blue border on focus for content tabs
-----------------------------------------------------------------------------------------

* Resolves: `#41176 <http://forge.typo3.org/issues/41176>`_
* Commit: `e418280 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=e418280244593116ccae75e1efbc5d07e37730c8>`_

[TASK] Add dashed border to active editable
-----------------------------------------------------------------------------------------

* Resolves: `#41174 <http://forge.typo3.org/issues/41174>`_
* Commit: `7844c21 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=7844c214cc9baf6af3b89838c81b3c588d5ce978>`_

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts the package as needed to consistently use the name
of TYPO3 Neos throughout code and other resources.

* Resolves: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `11d88b1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=11d88b1e3a967b51f9951321555ef08f816fe5e5>`_

[TASK] Clean up Routes.yaml (integer instead of boolean)
-----------------------------------------------------------------------------------------

* Commit: `0bae106 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=0bae1068d4251d0cdf0d7b64c549e90be5e72d29>`_

[TASK] Use getPartyByType to access currently logged in User
-----------------------------------------------------------------------------------------

This change explicitly uses getPartyByType for getting the current
User, as we would run into trouble if more than one account is
authenticated in the future (e.g. frontend user login).

* Commit: `f22eec5 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f22eec5135083a80a3a30e12425e179ff626d5da>`_

[FEATURE] Implement user dropdown menu
-----------------------------------------------------------------------------------------

This change moves the User Settings module to a new user dropdown
menu that also shows a logout link.

Additionally some styling issues of menu active state and
breadcrumb items were fixed.

* Resolves: `#41862 <http://forge.typo3.org/issues/41862>`_
* Commit: `41ad556 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=41ad556e57bb0858be0201a94204b1f3cf7ad90e>`_

[TASK] Fix JavaScript unit test
-----------------------------------------------------------------------------------------

The buster unit test checked for a wrong namespace URI.

* Commit: `fa662f5 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=fa662f52368839ec9f383344e541e65afb0aede2>`_

[BUGFIX] Enable publish button from applied changes in inspector
-----------------------------------------------------------------------------------------

Explicitly update the list of publishable nodes after a backbone
update and setting of the new workspacename of an entity. This
was suppressed in the backbone mode, so our entity wrapper would
not notice and VIE would not fire the change event.

* Resolves: `#42205 <http://forge.typo3.org/issues/42205>`_
* Commit: `cf3c228 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=cf3c228a113bffe21ea24989c84823772cda3bb6>`_

[TASK] Log exception thrown during site import step
-----------------------------------------------------------------------------------------

If an exception is thrown in the site import step, the exception
is now logged so that helpful details are available.

* Resolves: `#42316 <http://forge.typo3.org/issues/42316>`_
* Commit: `4aaf4fb <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4aaf4fbd557850569112c855ffbb8a1f9051272a>`_

[BUGFIX] Site name from import step not used
-----------------------------------------------------------------------------------------

The site name entered was never handed down to the site kickstarter.

* Fixes: `#42315 <http://forge.typo3.org/issues/42315>`_
* Commit: `f0689cb <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=f0689cbeb41ad989e35c170530da0660e7876c7c>`_

[TASK] Allow setting reloadable in the ContentElementWrapping
-----------------------------------------------------------------------------------------

Allows to set a wrapped content element as reloadable for
backend purposes.

* Commit: `1265be1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1265be1fe57aa6ddf4ab0508006393fe4ad9db81>`_

[TASK] Cleanup of aloha viewhelper
-----------------------------------------------------------------------------------------

Has been changed from <t:aloha.notEditable> to
<t:contentElement.notEditable>

* Commit: `43e8716 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=43e8716de19be7e787bffd5055ddde37fcddf013>`_

[BUGFIX] Remove 'Delete' button for currently logged in user
-----------------------------------------------------------------------------------------

In the user listing the delete button for the current user is disabled
so a user can not delete his own account. By going to the showAction of
the user the button was still visible though.

This change disables that button.

* Resolves: `#42217 <http://forge.typo3.org/issues/42217>`_
* Commit: `a7ee3f5 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a7ee3f5af9d747cc1ea60ee9ce9a3ef2be790636>`_

[TASK] Add hint for password requirements
-----------------------------------------------------------------------------------------

* Resolves: `#41857 <http://forge.typo3.org/issues/41857>`_
* Commit: `a30eb2b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=a30eb2b2d51cf147ecfa2a40e427df2c0ef832ff>`_

[TASK] Make it possible to press "enter" when creating a link
-----------------------------------------------------------------------------------------

* Resolves: `#41351 <http://forge.typo3.org/issues/41351>`_
* Commit: `4d601c8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4d601c8a578e4937f3e497d95bf974e554d59fbc>`_

[TASK] Move previewmode styling in correct position
-----------------------------------------------------------------------------------------

This was placed in the wrong place in I82caf5e298f20e8b3d5646dc5ff0819b1acacf2c

* Commit: `e98835b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=e98835b25ee8d5c7e825fc2c72585914a3b017d5>`_

[TASK] Update name on Twitter Bootstrap dependency
-----------------------------------------------------------------------------------------

* Commit: `af1c792 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=af1c79226becbaca6069c4a4c4eb321f53639426>`_

[FEATURE] Show saving indicator next to publish button
-----------------------------------------------------------------------------------------

This change adds an indicator next to the publish button that shows
if a save process is running and if and when the save was successful.

* Resolves: `#40709 <http://forge.typo3.org/issues/40709>`_
* Commit: `77f3521 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=77f3521d5658544382965d11d39c30edba17bc38>`_

[BUGFIX] Consistent user creation and workspace name
-----------------------------------------------------------------------------------------

This change implements a UserFactory that consistently
creates User objects for different use cases.

Furthermore, it removes the restriction of only-alphanumeric
usernames in the setup tool.

* Fixes: `#41972 <http://forge.typo3.org/issues/41972>`_
* Commit: `4b55091 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4b55091675d4e721a6ddd77d770d4925c193e1e6>`_

[TASK] Set title for Setup
-----------------------------------------------------------------------------------------

Needs https://review.typo3.org/#/c/15606/ for TYPO3.Setup.

* Fixes: `#41977 <http://forge.typo3.org/issues/41977>`_
* Commit: `3d35d90 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=3d35d9073f3520523070a0cdd916c7fc3a68c44c>`_

[FEATURE] Add specific policies for management/administration modules
-----------------------------------------------------------------------------------------

This introduces policies for the main modules allowing limiting access
to administration modules for normal users. The policies does not cover
overview modules, but their submodules. Also checks are added to the module
menu only showing them if the user has the specified role.

* Commit: `bf5e6ca <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=bf5e6ca6be2edfc49ecca0f2083427e0e9977843>`_

[TASK] Disable content element events in preview mode
-----------------------------------------------------------------------------------------

* Commit: `467b1e6 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=467b1e6b6b516a70a68bf7932f38110fdefdcb24>`_

[TASK] Hide active contentelement in preview mode
-----------------------------------------------------------------------------------------

* Related: `#42053 <http://forge.typo3.org/issues/42053>`_
* Commit: `403eac2 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=403eac28cccc05b6da0894fe565fc63e8ad51826>`_

[BUGFIX] Ignore Alt+L shortcut when editing content
-----------------------------------------------------------------------------------------

This allows @ signs to be inserted in content elements
on Mac OS.

* Fixes: `#41958 <http://forge.typo3.org/issues/41958>`_
* Commit: `4e7534d <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=4e7534d51d90bec3719ea485dc49d34dbecb1269>`_

[BUGFIX] Fix rendering test after change in ContentTypes package
-----------------------------------------------------------------------------------------

This just replaces the expected header comment to the new version.

* Commit: `bca38ba <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=bca38ba651c55cc3a11ab8181594036203a631fc>`_

[TASK] Add normalize styles
-----------------------------------------------------------------------------------------

* Resolves: `#41849 <http://forge.typo3.org/issues/41849>`_
* Commit: `dced49d <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=dced49dd1d2eb7e82c38c405a36c8e57d9718e8f>`_

[BUGFIX] Update documentation and replace all TYPO3 Phoenix with Neos
-----------------------------------------------------------------------------------------

* Commit: `797b934 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=797b93419dcccf32af6087fa4dee38a06a0c5ecd>`_

[BUGFIX] Fix basic rendering functional test
-----------------------------------------------------------------------------------------

* Commit: `aef0093 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=aef00937cc0030039fe3a81415cccf9e67cc88a9>`_

[TASK] Remove unused partials
-----------------------------------------------------------------------------------------

* Commit: `5224cdd <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=5224cdda08aa40fd68166fa7d4d61fbdff0445ad>`_

[TASK] Clean up routing exceptions & improve status codes
-----------------------------------------------------------------------------------------

Changes mentioned in I704d806c1c75dbad5edd01aec8d12d2fb773c8a1

* Commit: `1aa0eda <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=1aa0eda6574d908887d88fddb33d266071ffa380>`_

[TASK] Remove deprecated error controller and error views
-----------------------------------------------------------------------------------------

Deprecated with I704d806c1c75dbad5edd01aec8d12d2fb773c8a1

* Commit: `086dbb1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3.git?a=commit;h=086dbb1ef4c26271405c0024884b2177f37ac1b5>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Neos.ContentTypes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Working Content Menu
-----------------------------------------------------------------------------------------

The content menu template accessed a non existing node variable
in the template, this is now given by TypoScript.

Additionally the class of the ul element was changed to not
conflict with typical main menu classes.

* Commit: `9b8993c <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=9b8993cc756111ccf939935249fed27644a867f1>`_

[BUGFIX] Fixes bug where empty div was shown
-----------------------------------------------------------------------------------------

Fixes bug where empty div was shown on frontend. This div is only
needed when logged in.

* Fixes: `#41930 <http://forge.typo3.org/issues/41930>`_
* Commit: `ca87c63 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=ca87c63d0f5277def0af1ce8f2c5e11f4372110f>`_

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts the package as needed to consistently use the name
of TYPO3 Neos throughout code and other resources.

* Resolves: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `5616ebb <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=5616ebb54232a50fa39d0095945be10ff1d67b10>`_

[BUGFIX] t3-reloadable-content needs an id attribute
-----------------------------------------------------------------------------------------

The section now has t3-reloadable-content class but misses
an id attribute which leads to errors in the JavaScript.

* Fixes: `#42410 <http://forge.typo3.org/issues/42410>`_
* Commit: `cc5701d <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=cc5701d77cdd99a6bf97765ccd13d2d1f7015528>`_

[TASK] Remove comment line
-----------------------------------------------------------------------------------------

Commentline removed since it is generated in the output.
This should be an issue. Issue created: #41931

* Related: `#41928 <http://forge.typo3.org/issues/41928>`_
* Related: `#41931 <http://forge.typo3.org/issues/41931>`_

* Commit: `157761a <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=157761a285acb3f12db56fa494f354a9bae83dfb>`_

[TASK] Mark section and menu content as t3-reloadable-content
-----------------------------------------------------------------------------------------

This adds the necessary t3-reloadable-content class to section
and menu content elements in backend.

* Resolves: `#40714 <http://forge.typo3.org/issues/40714>`_
* Commit: `e1cbb6a <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=e1cbb6a204a004d181e5d30a2a374ab7ffe212cb>`_

[TASK] Phoenix in copyright source code header
-----------------------------------------------------------------------------------------

Replaced Phoenix by Neos

* Commit: `2552d92 <http://git.typo3.org/FLOW3/Packages/TYPO3.Phoenix.ContentTypes.git?a=commit;h=2552d9218c24a68ff9c6e73934ce1b4931f83dd5>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TYPO3CR
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Use regular class name in entity resource definition
-----------------------------------------------------------------------------------------

* Related: `#43629 <http://forge.typo3.org/issues/43629>`_
* Commit: `4860ef4 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=4860ef4f357742f842f40cc1ac3404f48a735a9a>`_

[TASK] Removed testable HTTP flag from tests
-----------------------------------------------------------------------------------------

Testable HTTP is now always enabled, thus the flag to switch it on can
be removed.

* Related: `#43590 <http://forge.typo3.org/issues/43590>`_
* Commit: `23fe453 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=23fe453e6cdcb9935b0b70bd20c8bf10e09dfdda>`_

[TASK] Decouple and simplify functional NodesTest
-----------------------------------------------------------------------------------------

The functional NodesTest was tied to Neos' ContentContext and had a ton
of code duplication. The duplicated code was moved to setUp() and the
test uses Service\\Context from the package itself now.

* Commit: `520c39b <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=520c39b43d792d89d9e3e8ba78d025eab4d33231>`_

[BUGFIX] Fix Version20120725073211.yaml up migration
-----------------------------------------------------------------------------------------

The YAML had a wrong indentation for the second filter definition.

* Fixes: `#42714 <http://forge.typo3.org/issues/42714>`_
* Commit: `044dd8a <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=044dd8a62e58f6abe912ef0155350a20bedabcaf>`_

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts the package as needed to consistently use the name
of TYPO3 Neos throughout code and other resources.

* Resolves: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `366dd21 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=366dd2134593a0fc964b030e8a8354a2e80c45cd>`_

[FEATURE] Add NodeName filter and RenameNode transformation
-----------------------------------------------------------------------------------------

This change adds a new filter to work on node names and a new
transformation to rename nodes.

* Commit: `19baebb <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=19baebbbea894f0c01cc51d8519d13ea486535c2>`_

[TASK] Add missing PostgreSQL migration
-----------------------------------------------------------------------------------------

The fix for #41873 (see I967721ab4cf140527ea7a03da85ffead093c2d69)
should have had a PostgreSQL migration. This change adds it.

* Related: `#41873 <http://forge.typo3.org/issues/41873>`_
* Commit: `ea9bca8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=ea9bca84628f2ca31bbdd8cb4ac9b29294980005>`_

[BUGFIX] Fix foreign key constraint during site:prune
-----------------------------------------------------------------------------------------

Foreign keys prevented removal of all nodes / workspaces. This broke
the site:prune command in the TYPO3.TYPO3 package. By setting the
baseWorkspace to NULL on delete the error is prevented and the
tables can be truncated.

* Fixes: `#41873 <http://forge.typo3.org/issues/41873>`_
* Commit: `32749fc <http://git.typo3.org/FLOW3/Packages/TYPO3.TYPO3CR.git?a=commit;h=32749fc1ee0212e2c93026e36511fcc47b4c22b5>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.TypoScript
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts some technically irrelevant uses of Phoenix to
Neos, just for completeness.

* Related: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `3c167c1 <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=3c167c19a7f546704a96a95b37fc0cb6322384e3>`_

[FEATURE] Implement Value object for simple values
-----------------------------------------------------------------------------------------

This change adds a Value TypoScript object to evaluate simple values
from paths (string, integer, Eel-Expression). Additionally a check is made
to prevent evaluation errors on paths that do not resolve to an array.

* Commit: `6e4c3b4 <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=6e4c3b4eb9213339749ab3f0f09533351fada560>`_

[FEATURE] Implement parsing of boolean constants
-----------------------------------------------------------------------------------------

This change adds the boolean constants TRUE and FALSE to the TypoScript Parser.

* Commit: `3cbc6e8 <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=3cbc6e8cebc8aac0500c440aba3fba765d3c92e1>`_

[BUGFIX] make sure collection does not loose context
-----------------------------------------------------------------------------------------

Furthermore, we add functional tests for it.

In order to be non-breaking, this change also requires
http://review.typo3.org/15754 to be merged.

* Commit: `8fd5640 <http://git.typo3.org/FLOW3/Packages/TYPO3.TypoScript.git?a=commit;h=8fd5640bb12199c86f03df7f5962eefad71d8f98>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Aloha
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts the package as needed to consistently use the name
of TYPO3 Neos.

* Related: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `111bca0 <http://git.typo3.org/FLOW3/Packages/TYPO3.Aloha.git?a=commit;h=111bca059ab11e80ad1bdda664133d35b29916a9>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Eel
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Avoid use of getPropertyInternal()
-----------------------------------------------------------------------------------------

The getPropertyInternal() method obviously is intended for internal use
in the ObjectAccess class. This change replaces it's use with a call to
getProperty().

* Related: `#43617 <http://forge.typo3.org/issues/43617>`_
* Commit: `17f52a4 <http://git.typo3.org/FLOW3/Packages/TYPO3.Eel.git?a=commit;h=17f52a415fb06e667440e9903a57554d9a56db9e>`_

[TASK] Implement \\Countable in FlowQuery
-----------------------------------------------------------------------------------------

This makes FlowQuery objects behave more like arrays.

* Commit: `3c92516 <http://git.typo3.org/FLOW3/Packages/TYPO3.Eel.git?a=commit;h=3c92516a303bea9a368c31dbd566481a1f364ab5>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.ExtJS
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Adjust ExtDirect RequestHandler to Flow changes
-----------------------------------------------------------------------------------------

This adjusts the RequestHandler to the recent changes in Flow regarding
session handling. For this the usual HTTP response is now used.

Some additional cleanup is done and error handling has been added.

* Fixes: `#43611 <http://forge.typo3.org/issues/43611>`_
* Fixes: `#27665 <http://forge.typo3.org/issues/27665>`_

* Commit: `584f77e <http://git.typo3.org/FLOW3/Packages/TYPO3.ExtJS.git?a=commit;h=584f77e0d0d3d8503bf765e1d6157ef47e2fab8d>`_

[BUGFIX] Fix Ext.EventManager is undefined error
-----------------------------------------------------------------------------------------

This change packages a new version of ExtDirect including the EventManager,
and some other dependencies. Works in FireFox and Chrome now. Please test
without browser cache enabled. Have a close look if pagetree / publishing
and creating content still works.

* Resolves: `#41047 <http://forge.typo3.org/issues/41047>`_
* Commit: `90f45e0 <http://git.typo3.org/FLOW3/Packages/TYPO3.ExtJS.git?a=commit;h=90f45e024c1d42b63958e041a4fc4c80e05d9223>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Form
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Adjust code & template to recent Fluid changes
-----------------------------------------------------------------------------------------

With Ifa4ccaafb550526ec977d93059ca123b18ef5462 one of
the arguments "action" and "actionUri" is required.

This change adjusts the Form template accordingly.

Besides this replaces the deprecated method setFormActionUri()
in the subclassed FormViewHelper

* Related: `#43589 <http://forge.typo3.org/issues/43589>`_
* Commit: `ca1590b <http://git.typo3.org/FLOW3/Packages/TYPO3.Form.git?a=commit;h=ca1590b0e5e6eb174b81af254df2f6190e112f07>`_

[TASK] Update tests to new validator supportedOptions property
-----------------------------------------------------------------------------------------

* Related: `#37820 <http://forge.typo3.org/issues/37820>`_
* Commit: `17c1d68 <http://git.typo3.org/FLOW3/Packages/TYPO3.Form.git?a=commit;h=17c1d68862875009ef94ec4c8560d0debeac187b>`_

[TASK] Add description property to all form fields
-----------------------------------------------------------------------------------------

This change adds a new form element property "elementDescription"
to every element using the Field layout.

It also adds a special property "passwordDescription" for the
PasswordWithConfirmation element for placing the hint directly
after the first password input field.

* Resolves: `#41857 <http://forge.typo3.org/issues/41857>`_
* Commit: `1aec7b4 <http://git.typo3.org/FLOW3/Packages/TYPO3.Form.git?a=commit;h=1aec7b4f29efd3d2f4d999d11788d94d621277fb>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Imagine
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Fix Imagine name in requirements of manifest
-----------------------------------------------------------------------------------------

Officially Imagine is "imagine/Imagine", not "imagine/imagine".

* Commit: `202eda6 <http://git.typo3.org/FLOW3/Packages/TYPO3.Imagine.git?a=commit;h=202eda6ba3c4f30dbcfc14488c77c73d4b85e848>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Media
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[FEATURE] Support ImageVariants in ImageViewHelper
-----------------------------------------------------------------------------------------

Adjusts the signature of both image ViewHelpers to expect
an ImageInterface instead of the actual image in order to
accept to render ImageVariant instances.

Besides this makes sure that ImageInterface::getThumbnail()
is only called if required (if width/height of the image to
render are not equal to the actual image dimensions)

* Commit: `abd172c <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=abd172ce7ad9a8e7ca3d079854875f71209ea824>`_

[!!!][FEATURE] Support image variants in transient images
-----------------------------------------------------------------------------------------

Previously an image had a reference to its repository in order
to persist itself as soon as a variant was added or removed.

This leads to a very tight coupling and prevented image variants
to be used on images that are not persisted (yet).

The change removes the ImageRepository::update() call from
Image::createImageVariant() and Image:removeImageVariant()

This is a breaking change in case you relied on the automatic
persistence of new image variants. In this case you need to adjust
your code and persist the image manually:

$image->createImageVariant(...);
$this->imageRepository->update($image);

* Commit: `b8c3b12 <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=b8c3b12fb40dd42614945a6affee933d90b7c966>`_

[FEATURE] Provide alias handling for ImageVariants
-----------------------------------------------------------------------------------------

Reusing of ImageVariants is now enhanced by providing alias names
for ImageVariants, for example "small" or "medium" etc.

Convient methods for handling with these aliases are provided, this
includes, for example, removing Variants by their alias.

An alias is simply created with passing an additional, optional
`alias` argument to the Image's createImageVariant() method.

* Related: `#38782 <http://forge.typo3.org/issues/38782>`_
* Commit: `a9f5b76 <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=a9f5b765375a8cf6909c601d8e5887d10e51b1bf>`_

[TASK] Update tests to new validator supportedOptions property
-----------------------------------------------------------------------------------------

* Related: `#37820 <http://forge.typo3.org/issues/37820>`_
* Commit: `0ba6cde <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=0ba6cdecea1f00f7f5ed55029e4c2f346770985b>`_

[BUGFIX] Fix array key check in ImageConverter
-----------------------------------------------------------------------------------------

Using isset() to check for array keys may fail when used on strings.
This changes uses array_key_exists().

* Fixes: `#42749 <http://forge.typo3.org/issues/42749>`_
* Commit: `86f4144 <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=86f4144a7d9ae1b994a7db9f3acee21cd0076728>`_

[BUGFIX] Fix Image TypeConverter
-----------------------------------------------------------------------------------------

The Image TypeConverter now takes care about a given `title` property
and takes it into account for the mapping; additionally the handling of
mapping persisted Images (i.e. sources with an ``__identity`` property)
works as it used to be for persisted entities.

This is covered by some additional Functional Tests, which have,
besides, been augmented a bit and refactored to an abstract functional
test case for reusable methods.

* Fixes: `#36959 <http://forge.typo3.org/issues/36959>`_
* Fixes: `#37230 <http://forge.typo3.org/issues/37230>`_

* Commit: `27d2c52 <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=27d2c527942a3183fc2a5e9b36eb860359ace897>`_

[BUGFIX] Add schema migraton for serialized imageVariants
-----------------------------------------------------------------------------------------

The ``imageVariant`` property of the ``Image`` entity is
string-replaced from FLOW3 to Flow legacies.

* Fixes: `#41891 <http://forge.typo3.org/issues/41891>`_
* Commit: `0e53e00 <http://git.typo3.org/FLOW3/Packages/TYPO3.Media.git?a=commit;h=0e53e003b4952e41f9ecad7dfb2910678429033b>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Setup
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[BUGFIX] Adjust code & template to recent Fluid changes
-----------------------------------------------------------------------------------------

With Ifa4ccaafb550526ec977d93059ca123b18ef5462 one of
the arguments "action" and "actionUri" is required.

This change adjusts the Form template accordingly

* Related: `#43589 <http://forge.typo3.org/issues/43589>`_
* Commit: `679fc71 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=679fc71861c05c8d579e55dee3ee6833f0c3d411>`_

[TASK] Add optional notice as tooltip on skip button
-----------------------------------------------------------------------------------------

This change adds a new rendering option for the form definition
of setup steps to display a notice on skip buttons as a tooltip.

* Resolves: `#42209 <http://forge.typo3.org/issues/42209>`_
* Commit: `388e314 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=388e31451bf564a61b26299953dc28885ae8b425>`_

[TASK] Adjust to changed exception handling in DBAL
-----------------------------------------------------------------------------------------

Doctrine 2.3 adds some changes to DBAL when it comes to handling
PDO exceptions. In some cases these are now transformed into
DBAL exemptions, requiring some adjustments in setup.

* Commit: `6a846e9 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=6a846e95cf014ce0a3430445bc4ee30896b9b6b7>`_

[BUGFIX] Hide form buttons in final step
-----------------------------------------------------------------------------------------

Introduced in I156085e103deabd4b477dc873ee1ea9cb4579c79

* Commit: `ba2a037 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=ba2a037d09d1f60c9b76089d04ee112b22dcf0d7>`_

[TASK] Use TYPO3 Setup instead of TYPO3 Neos Setup
-----------------------------------------------------------------------------------------

* Commit: `b18c645 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=b18c6451a7d77790294a4190a26dd54fd49d1f88>`_

[TASK] Quote database name upon creation
-----------------------------------------------------------------------------------------

It is possible to use database names like "test-development" but it
was not possible to actually create them using the setup tool.

This change adds identifier quoting to the database creation step.

* Resolves: `#40894 <http://forge.typo3.org/issues/40894>`_
* Commit: `1772896 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=17728960477dbeafc66a8fdae61aa23c35dfca21>`_

[BUGFIX] Use template1 to read database names
-----------------------------------------------------------------------------------------

Setup tries to establish a connection without a database name to fetch
the list of databases. This is not possible on PostgreSQL.

This changes uses "template1" instead.

* Fixes: `#42301 <http://forge.typo3.org/issues/42301>`_
* Commit: `4086a03 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=4086a03da1749c5f60120e838c935c7276e2f5f9>`_

[TASK] Styling for password requirements hint
-----------------------------------------------------------------------------------------

* Resolves: `#41857 <http://forge.typo3.org/issues/41857>`_
* Commit: `a4b5cf7 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=a4b5cf73fe58c11a2458c5458228f4bac3f01670>`_

[TASK] Unbootstrapify the setup screens
-----------------------------------------------------------------------------------------

* Resolves: `#41854 <http://forge.typo3.org/issues/41854>`_
* Commit: `ff795cb <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=ff795cbfb48eff9aa72e24825dd3c6fdd24000b4>`_

[TASK] Rename requirement on Twitter Bootstrap Package
-----------------------------------------------------------------------------------------

* Commit: `c6feff5 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=c6feff5e8ed50f845ad196a3d6ce5e541d245a4c>`_

[FEATURE] Configurable title inside setup wizard
-----------------------------------------------------------------------------------------

* Fixes: `#41977 <http://forge.typo3.org/issues/41977>`_
* Commit: `3003072 <http://git.typo3.org/FLOW3/Packages/TYPO3.Setup.git?a=commit;h=3003072d39daaf92a323be96608897ac2a284216>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.SiteKickstarter
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Rename Phoenix to Neos
-----------------------------------------------------------------------------------------

This change adjusts the package as needed to consistently use the name
of TYPO3 Neos throughout code and other resources.

* Resolves: `#41668 <http://forge.typo3.org/issues/41668>`_
* Commit: `47d1499 <http://git.typo3.org/FLOW3/Packages/TYPO3.SiteKickstarter.git?a=commit;h=47d14991f69b228f6a0aee9b0d7c0a57cb9450ba>`_

[BUGFIX] Fall back to full package key if no dot present
-----------------------------------------------------------------------------------------

Since the composer integration a package key without a dot is
technically valid. The site kickstarter depended on a dot being present
and generates invalid Sites.xml if the dot is not found.

This change adds a check and falls back to using the package key for the
site node name if needed.

* Fixes: `#42309 <http://forge.typo3.org/issues/42309>`_
* Commit: `c0c8836 <http://git.typo3.org/FLOW3/Packages/TYPO3.SiteKickstarter.git?a=commit;h=c0c8836d6de9498989cfcee65d8b13bbfccf3d6f>`_

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
TYPO3.Twitter.Bootstrap
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

[TASK] Allow HTML code in menu label
-----------------------------------------------------------------------------------------

By allowing HTML code in the label it's possible to add for example
a badge to a menu item.

* Commit: `dacbb5e <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=dacbb5e42b6db5dcee090f576748c940756cebda>`_

[FEATURE] Icons for the navigation items
-----------------------------------------------------------------------------------------

If your menu items should show a nice icon next to them, you can just
define an iconClass for a menu item and it will be shown right in front
of the menu item.

* Resolves: `#42012 <http://forge.typo3.org/issues/42012>`_
* Commit: `c1343bc <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=c1343bce953a2d81419b60cac3b83fec6fa22318>`_

[!!!][TASK] Change composer-name and adjust namespace
-----------------------------------------------------------------------------------------

This package should not be published under the Twitter
vendorname, but use TYPO3.

Note: Namespace is changed, most be adjusted in client code

* Commit: `514322c <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=514322c57533f3eb0590e464f3f369df55b76937>`_

[FEATURE] Include jQuery library in include view helper
-----------------------------------------------------------------------------------------

Adds the jQuery library (v1.8.2) files and makes them optionally
includable in the include view helper (by default jQuery is not
included).

* Resolves: `#41959 <http://forge.typo3.org/issues/41959>`_
* Commit: `600e524 <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=600e524a8a76a3afd7635e7bb0e84ecff14ce606>`_

[TASK] Add basic documentation
-----------------------------------------------------------------------------------------

* Resolves: `#41961 <http://forge.typo3.org/issues/41961>`_
* Commit: `0c4b53e <http://git.typo3.org/FLOW3/Packages/Twitter.Bootstrap.git?a=commit;h=0c4b53edc5962bf0e8991d7b01c86ca1be555963>`_

