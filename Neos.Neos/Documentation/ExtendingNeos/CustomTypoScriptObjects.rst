.. _custom-typoscript-objects:

Custom TypoScript Objects
=========================

By adding custom TypoScript Objects it is possible to extend the capabilities of TypoScript in a powerful and configurable
way. If you need to write a way to execute PHP code during rendering, for simple methods, Eel helpers should be used.
For more complex functionality where custom classes with more configuration options are needed, TypoScript objects should
rather be created.

As an example, you might want to create your own TypoScript objects if you are enriching the data that gets passed to the
template with external information from an API or if you have to convert some entities from identifier to domain objects.

In the example below, a Gravatar image tag is generated.

Create a TypoScript Object Class
--------------------------------

To create a custom TypoScript object the ``Neos\Fusion\FusionObjects\AbstractFusionObject`` class is
extended. The only method that needs to be implemented is ``evaluate()``. To access values from TypoScript the method
``$this->tsValue('__ts_value_key__');`` is used:

.. code-block:: php

	namespace Vendor\Site\TypoScript;

	use Neos\Flow\Annotations as Flow;
	use Neos\Fusion\FusionObjects\AbstractFusionObject

	class GravatarImplementation extends AbstractFusionObject {
		public function evaluate() {
			$emailAddress = $this->tsValue('emailAddress');
			$size = $this->tsValue('size') ? $this->tsValue('size') : 80;
			$gravatarImageSource = 'http://www.gravatar.com/avatar/' . md5( strtolower( trim( $emailAddress ) ) ) . "?s=$size&d=mm&r=g";
			return '<img src="' . $gravatarImageSource . '" alt="" />';
		}
	}

To use this implementation in Fusion, you have to define a Fusion-prototype first::

	prototype(Vendor.Site:Gravatar) {
		@class = 'Vendor\\Site\\Fusion\\GravatarImplementation'
		emailAddress = ''
		size = 80
	}

Afterwards the prototype can be used in Fusion::

	garavatarImage = Vendor.Site:Gravatar
	garavatarImage {
		emailAddress = 'kasper@typo3.org'
		size = 120
	}
