.. _custom-backend-modules:

Custom Backend Modules
======================

If you want to integrate custom tools into the neos-backend you can do so by adding a submodule to the
administration- or the management-section of the main menu.

Some possible use cases would be the integrating of mass editing options, triggering of import or export
actions or creating of editing interfaces for DomainModels from FlowPackages.

.. warning:: This is no pubic api yet and still might change in future releases of Neos.

Controller Class
----------------

Implementing a Backend Module starts by creating an action controller class derived from
``\TYPO3\Flow\Mvc\Controller\ActionController``

*Classes/Vendor/Site/Domain/Controller/BackendController*:

.. code-block:: php

	namespace Vendor\Site\Controller;

	use TYPO3\Flow\Annotations as Flow;

	class BackendController extends \TYPO3\Flow\Mvc\Controller\ActionController {
		public function indexAction() {
			$this->view->assign('exampleValue', 'Hello World');
		}
	}

Fluid Template
--------------

The user interface of the module is defined in a fluid template in the same way the frontend of a website is defined.

*Resources/Private/Templates/Backend/Index.html*:

.. code-block:: html

	{namespace neos=TYPO3\Neos\ViewHelpers}
	<div class="neos-content neos-container-fluid">
		<h1></h1>
		<p>{exampleValue}</p>
	</div>

.. note:: Neos comes with some ViewHelpers for easing Backend Tasks. Have a look at the neos:backend... ViewHelpers
	from the :ref:`Neos ViewHelper Reference`

Access Rights
-------------

To use the module the editors have to be granted access to the controller actions of the module.

*Configuration/Policy.yaml*:

.. code-block:: yaml

	privilegeTargets:

	  'TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':
		'Vendor.Site:BackendModule':
		  matcher: 'method(Vendor\Site\Controller\BackendController->.*Action())'

	roles:

	  'TYPO3.Neos:Editor':
		privileges:
		  -
			privilegeTarget: 'Vendor.Site:BackendModule'
			permission: GRANT

Configuration
-------------

To show up in the management or the administration section the module is defined in the package settings.

*Configuration/Settings.yaml*:

.. code-block:: yaml

	TYPO3:
	  Neos:
		modules:
		  management:
			submodules:
			  exampleModule:
				label: 'Example Module'
				controller: 'Vendor\Site\Controller\BackendController'
				description: 'An Example for implementing Backend Modules'
				icon: 'icon-star'
				privilegeTarget: 'Vendor.Site:BackendModule'
