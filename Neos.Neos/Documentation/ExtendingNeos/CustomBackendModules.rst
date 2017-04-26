.. _custom-backend-modules:

Custom Backend Modules
======================

If you want to integrate custom backend functionality you can do so by adding a submodule to the
administration or management section of the main menu. Alternatively a new top level section can
be created either by adding a overview module like the the existing ones or a normal module.

Some possible use cases would be the integrating of external web services, triggering of import or export
actions or creating of editing interfaces for domain models from other packages.

.. warning:: This is not public API yet due to it's unpolished state and is subjectable to change in the future.

Controller Class
----------------

Implementing a Backend Module starts by creating an action controller class derived from
``\Neos\Flow\Mvc\Controller\ActionController``

``Classes/Vendor/Site/Domain/Controller/BackendController``:

.. code-block:: php

	namespace Vendor\Site\Controller;

	use Neos\Flow\Annotations as Flow;

	class BackendController extends \Neos\Flow\Mvc\Controller\ActionController {
		public function indexAction() {
			$this->view->assign('exampleValue', 'Hello World');
		}
	}

Fluid Template
--------------

The user interface of the module is defined in a fluid template in the same way the frontend of a website is defined.

``Resources/Private/Templates/Backend/Index.html``:

.. code-block:: html

	{namespace neos=Neos\Neos\ViewHelpers}
	<div class="neos-content neos-container-fluid">
		<h1></h1>
		<p>{exampleValue}</p>
	</div>

.. note:: Neos comes with some ViewHelpers for easing backend tasks. Have a look at the ``neos:backend`` ViewHelpers
   from the :ref:`Neos ViewHelper Reference`

Configuration
-------------

To show up in the management or the administration section the module is defined in the package settings.

``Configuration/Settings.yaml``:

.. code-block:: yaml

	Neos:
	  Neos:
	    modules:
	      'management':
	        submodules:
	          'exampleModule':
	            label: 'Example Module'
	            controller: 'Vendor\Site\Controller\BackendController'
	            description: 'An Example for implementing Backend Modules'
	            icon: 'icon-star'

Access Rights
-------------

To use the module the editors have to be granted access to the controller actions of the module.

``Configuration/Policy.yaml``:

.. code-block:: yaml

	privilegeTargets:
	
	  'Neos\Neos\Security\Authorization\Privilege\ModulePrivilege':
	
	    'Vendor.Site:BackendModule':
	      matcher: 'management/exampleModule'
	
	roles:
	
	  'Neos.Neos:Editor':
	    privileges:
	      -
	        privilegeTarget: 'Vendor.Site:BackendModule'
	        permission: GRANT


.. tip:: Neos contains several backend modules build with the same API which can be used for inspiration.
