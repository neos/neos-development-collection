if (typeof VIE === 'undefined') {
	VIE = {};
}

(function($){
	$.fn.vieSemanticAloha = function() {
		this.each(function() {
			var containerInstance = VIE.ContainerManager.getInstanceForContainer(jQuery(this));
			containerInstance.editables = {};
			jQuery(containerInstance.view.el).find('[property]').each(function() {
				var containerProperty = jQuery(this);
				var propertyName = containerProperty.attr('property');
				containerInstance.editables[propertyName] = new GENTICS.Aloha.Editable(containerProperty);
				containerInstance.editables[propertyName].vieContainerInstance = containerInstance;
			});
		})
	}
})(jQuery);

VIE.Aloha = {
	saveModified: function(success) {
		jQuery.each(VIE.ContainerManager.instances, function() {
			jQuery.each(this.editables, function() {
				if (this.isModified()) {
					VIE.Aloha.saveEditable(this, success);
				}
			});
		});
	},

	saveEditable: function(editable, success) {
		var containerInstance = editable.vieContainerInstance,
			modifiedProperties = {};
		jQuery.each(containerInstance.editables, function(propertyName, editable) {
			if (editable.isModified()) {
				modifiedProperties[propertyName] = editable.getContents();
			}
		});
			// containerInstance.set(modifiedProperties);
		containerInstance.save(modifiedProperties, function() {
			success(editable);
		});
	}
};
