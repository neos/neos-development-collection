Ext.ns("F3.TYPO3.Content.AlohaConnector");

F3.TYPO3.Content.AlohaConnector.onChange = function(evt, contents) {
	var html = contents.editable.getContents();
	if (typeof window.parent.F3 !== 'undefined') {
		window.parent.F3.TYPO3.Application.fireEvent('F3.TYPO3.Application.AlohaConnector.contentChanged', {
			identity: contents.editable.obj[0].getAttribute('data-identity'),
			html: html
		});
	}
}

GENTICS.Aloha.EventRegistry.subscribe(GENTICS.Aloha, 'editableDeactivated', F3.TYPO3.Content.AlohaConnector.onChange);