define(
	[
		'./Button'
	],
	function (Button) {
		return Button.extend({
			label: 'Publish Page',
			disabled: function() {
				return this.get('_noChanges') || this.get('_saveRunning');
			}.property('_noChanges', '_saveRunning'),
			target: 'T3.Content.Model.PublishableNodes',
			action: 'publishAll',
			_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
			_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
			_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',
			classNameBindings: ['connectionStatusClass'],
			classNames: ['btn-publish'],

			connectionStatusClass: function() {
				var className = 't3-connection-status-';
				className += this.get('_connectionFailed') ? 'down' : 'up';
				return className;
			}.property('_connectionFailed')
		});
	}
);