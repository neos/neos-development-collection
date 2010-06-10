Ext.form.Action.F3DirectSubmit = Ext.extend(Ext.form.Action.DirectSubmit, {
    run: function() {
        var o = this.options,
			values;
        if (o.clientValidation === false || this.form.isValid()) {
            this.success.params = this.getParams();
			values = Ext.apply(this.form.getValues(), o.additionalValues);
            this.form.api.submit(values, this.success, this);
        } else if (o.clientValidation !== false) {
            this.failureType = Ext.form.Action.CLIENT_INVALID;
            this.form.afterAction(this, false);
        }
    }
});
Ext.form.Action.ACTION_TYPES['directsubmit'] = Ext.form.Action.F3DirectSubmit;

Ext.Direct.on('exception', function(event) {
	if (window.console && console.error) {
		console.error(event.message, event.where);
	}
});
