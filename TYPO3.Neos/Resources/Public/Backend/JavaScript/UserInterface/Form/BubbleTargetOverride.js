/**
 * Override Ext.form.Field so the specialkey event bubbles to the parent form.
 * We need this override because some content dialogs have nested form structures,
 * so the specialkey event would not be registered to the sub form elements.
 */
Ext.override(Ext.form.Field, {

	/**
	 * Add functionality to Field's initComponent to enable the change event to bubble
	 * @return {void}
	 */
	initComponent : Ext.form.Field.prototype.initComponent.createSequence(function() {
		this.enableBubble('specialkey');
	}),

	/**
	 * We know that we want Field's events to bubble directly to the FormPanel.
	 * @return {void}
	 */
	getBubbleTarget : function() {
		if (!this.formPanel) {
			this.formPanel = this.findParentByType('form');
		}
		return this.formPanel;
	}

});