Ext.ns('F3.TYPO3.Components');

F3.TYPO3.Components.Button = Ext.extend(Ext.Button, {
	buttonSelector: 'button span:first-child',
	menuClassTarget: 'button span',

	template: new Ext.Template(
		'<div id="{2}" class="F3-TYPO3-Components-Button"><button type="{0}" class="{1}"><span></span></button></div>'
	).compile(),

	getTemplateArgs: function() {
        return [this.type, this.cls + ' F3-TYPO3-Components-Button-scale-' + this.scale, this.id];
    }
});
Ext.reg('F3.TYPO3.Components.Button', F3.TYPO3.Components.Button);
