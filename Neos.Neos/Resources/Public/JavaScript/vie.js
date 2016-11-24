define(['Library/jquery-with-dependencies', 'Library/vie'], function($, VIE) {
	var vieInstance = new VIE();

	if (!vieInstance.namespaces.get('typo3')) {
		vieInstance.namespaces.add('typo3', 'http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/');
	}
	if (!vieInstance.namespaces.get('xsd')) {
		vieInstance.namespaces.add('xsd', 'http://www.w3.org/2001/XMLSchema#');
	}

	/**
	 * We're monkey-patching the VIE RDFa-Service, in order to be able to read and write data-node-* attributes
	 * instead of RDFa tags.
	 */
	var originalRdfaServiceReadEntityPredicatesFn = vieInstance.RdfaService.prototype._readEntityPredicates;
	vieInstance.RdfaService.prototype._readEntityPredicates = function(subject, element, emptyValues) {
		var service = this;
		var entityPredicates = originalRdfaServiceReadEntityPredicatesFn.apply(this, arguments);

		$.each(element.get(0).attributes, function(i, attribute) {
			if (attribute.name.substr(0, 10) === 'data-node-' && element.attr('typeof') !== undefined) {
				var value = attribute.value;
				var propertyName = attribute.name.substr(10);
				var dataType = element.data('nodedatatype-' + propertyName);

				if (dataType) {
					var fullyQualifiedDataType = service.vie.namespaces.uri(dataType);
					if (service.datatypeReaders[fullyQualifiedDataType]) {
						value = service.datatypeReaders[fullyQualifiedDataType](value);
					}
				}

				propertyName = propertyName.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });

				entityPredicates['typo3:' + propertyName] = value;
			}
		});

		return entityPredicates;
	};

	var originalRdfaServiceWriteEntityFn = vieInstance.RdfaService.prototype._writeEntity;
	vieInstance.RdfaService.prototype._writeEntity = function(entity, element) {
		var service = this;

		$.each(element.attributes, function(i, attribute) {
			if (attribute.name.substr(0, 10) === 'data-node-') {
				var propertyName = attribute.name.substr(10);
				var dataType = $(element).data('nodedatatype-' + propertyName);

				propertyName = propertyName.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });

				var valueFromEntity = entity.get('typo3:' + propertyName);

				if (dataType) {
					var fullyQualifiedDataType = service.vie.namespaces.uri(dataType);
					if (service.datatypeWriters[fullyQualifiedDataType]) {
						valueFromEntity = service.datatypeWriters[fullyQualifiedDataType](valueFromEntity);
					}
				}

				attribute.value = valueFromEntity;
			}
		});

		return originalRdfaServiceWriteEntityFn.apply(this, arguments);
	};

	vieInstance.use(new vieInstance.RdfaService());
	vieInstance.Util = VIE.Util;
	return vieInstance;
});