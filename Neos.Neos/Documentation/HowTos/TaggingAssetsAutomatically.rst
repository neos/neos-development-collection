.. _cookbook-tagging-assets-automatically:

============================
Tagging assets automatically
============================

Uploaded assets like images, documents or media files can be assigned to `Tags` and `AssetCollections` manually
in the `Media` module.
Especially for sites with many assets it is useful to automate this in order to keep files organized.

Asset Collection based on site
==============================

Sites can already be assigned to an `AssetCollection` in the `Sites Management` module.
If that is the case, any asset uploaded to a node within that site will automatically be added
to the corresponding `AssetCollection`.
This is especially useful in order to keep files of multi-site installations separated.

For more fine-granular manipulation the ``ContentController::assetUploaded`` signal can be used to
alter assets based on the node they were attached to:

Hooking into the asset creation
===============================

In order to hook into the asset creation, a new signal/slot connection has to be established.
For this a new `Package.php` (usually in `Packages/Site/The.Site/Classes/`) has to be added:

*Example: Package.php* ::

	<?php
	namespace Some\Package;

	use Neos\Flow\Core\Bootstrap;
	use Neos\Flow\Package\Package as BasePackage;
	use Neos\Neos\Controller\Backend\ContentController;

	class Package extends BasePackage
	{
	    public function boot(Bootstrap $bootstrap)
	    {
	        $dispatcher = $bootstrap->getSignalSlotDispatcher();
	        $dispatcher->connect(ContentController::class, 'assetUploaded', AssetManipulator::class, 'manipulateAsset');
	    }
	}

.. note::

    If you created a new ``Package.php`` file you need to run `./flow flow:package:rescan` in order for Flow to pick it up!

The slot gets called with the following arguments:

* The ``Asset`` instance that is about to be persisted
* The ``NodeInterface`` instance the asset has been attached to
* The node property name (``string``) the asset has been assigned to

So the signature of the slot method could look like this::

	function theSlot(Asset $asset, NodeInterface $node, string $propertyName)

This allows for manipulation of the asset based on the node property it has been assigned to.

Example: Tagging employee images
================================

Imagine you have a node type `Employee` with the following setup::

	'Some.Package:Employee':
	  superTypes:
	    'Neos.Neos:Content': true
	  ui:
	    label: 'Employee'
	    inspector:
	      groups:
	        'employee':
	          label: 'Employee'
	  properties:
	    'image':
	      type: 'Neos\Media\Domain\Model\ImageInterface'
	      ui:
	        label: 'Employee profile picture'
	        reloadIfChanged: true
	        inspector:
	          group: 'employee'
	          editorOptions:
	            features:
	              mediaBrowser: false

The following code would automatically tag this with the `employee` tag (if it exists):

*Example: AssetManipulator.php* ::

	<?php
	namespace Some\Package;

	use Neos\ContentRepository\Domain\Model\NodeInterface;
	use Neos\Flow\Annotations as Flow;
	use Neos\Media\Domain\Model\Asset;
	use Neos\Media\Domain\Repository\TagRepository;

	/**
	 * @Flow\Scope("singleton")
	 */
	class AssetManipulator
	{
	    /**
	     * @Flow\Inject
	     * @var TagRepository
	     */
	    protected $tagRepository;

	    public function assignTag(Asset $asset, NodeInterface $node, string $propertyName)
	    {
	        if (!$node->getNodeType()->isOfType('Some.Package:Employee') || $propertyName !== 'image') {
	            return;
	        }
	        $employeeTag = $this->tagRepository->findOneByLabel('employee');
	        if ($employeeTag === null) {
	            return;
	        }
	        $asset->addTag($employeeTag);
	    }
	}

Alternatively, the slot could also assign the asset to `AssetCollections` or alter the asset's `title` or `caption`.
