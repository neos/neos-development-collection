Developer Information
=====================

Asset Usage Strategies
----------------------

It is possible to extend the media handling by defining asset usage strategies. Those
strategies can tell the media package if an asset is in used, how many times it is
used and how it is used.

An asset usage strategy is already implemented for Neos ContentRepository nodes under the sites root,
like document and content nodes. For all other usage scenarios, you need to build your own strategy.

To define your own custom usage strategy you have to implement the
``Neos\Media\Domain\Strategy\AssetUsageStrategyInterface``. For convenience you can
extend the ``Neos\Media\Domain\Strategy\AbstractAssetUsageStrategy``.

Example Strategy
****************

.. code-block:: php

	use TYPO3\Flow\Annotations as Flow;
	use Neos\Media\Domain\Strategy\AbstractAssetUsageStrategy;
	use TYPO3\Flow\Persistence\PersistenceManagerInterface;

	/**
	 * @Flow\Scope("singleton")
	 */
	class MyCustomAssetUsageStrategy extends AbstractAssetUsageStrategy
	{
	    /**
	     * @Flow\Inject
	     * @var PersistenceManagerInterface
	     */
	    protected $persistenceManager;

	    /**
	     * @var array
	     */
	    protected $firstlevelCache = [];

	    /**
	     * Returns an array of usage reference objects.
	     *
	     * @param AssetInterface $asset
	     * @return array<\Neos\Media\Domain\Model\Dto\UsageReference>
	     */
	    public function getUsageReferences(AssetInterface $asset)
	    {
	        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
	        if (isset($this->firstlevelCache[$assetIdentifier])) {
	            return $this->firstlevelCache[$assetIdentifier];
	        }

	        // Your code to find asset usage
	        foreach ($usages as $usage) {
	            $this->firstlevelCache[$assetIdentifier] = new \Neos\Media\Domain\Model\Dto\UsageReference($asset);
	        }

	        return $this->firstlevelCache[$assetIdentifier];
	    }
	}

Extend Asset Validation
-----------------------

Imagine you need to extend the validation of assets. For example to prevent
duplicate file names or to run copyright checks on images. You can do so
by creating your own custom validator. If you make sure that your validator
implements the ``\Neos\Media\Domain\Validator\AssetValidatorInterface`` it
will be loaded on object validation. The added errors in your validator will
be merged into the model validator of assets.

Example validator
*****************

.. code-block:: php

	<?php
	namespace My\Package;

	use TYPO3\Flow\Validation\Valwidator\AbstractValidator;
	use Neos\Media\Domain\Model\AssetInterface;
	use Neos\Media\Domain\Validator\AssetValidatorInterface;

	class CustomValidator extends AbstractValidator implements AssetValidatorInterface
	{

	    /**
	     * Check if $value is valid. If it is not valid, needs to add an error
	     * to the result.
	     *
	     * @param AssetInterface $value
	     * @return void
	     */
	    protected function isValid($value)
	    {
	        // Your object validation
	        if ($errors) {
	            $this->addError('Some error', 0123456789);
	        }
	    }
	}
