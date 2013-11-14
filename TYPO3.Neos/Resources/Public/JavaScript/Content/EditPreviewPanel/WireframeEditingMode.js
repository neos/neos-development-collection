define(
[
	'./EditingMode',
	'Shared/Notification'
], function(
	EditingMode,
	Notification
) {
	var ContentModule;

		// HACK!!!
	window.setTimeout(function() {
		require(['Content/ContentModule'], function(c) {
			ContentModule = c;
		})
	}, 0);

	return EditingMode.extend({
		identifier: 'templateFree',
		title: 'Raw Content',

		_connectCreateContentCollectionButton: function() {
			var that = this;

			$('#neos-createcontentcollection-input').keypress(function(e) {
				if ((e.keyCode || e.which) === 13) {
					$('#neos-createcontentcollection-button').click();
				}
			});

			$('#neos-createcontentcollection-button').click(function() {
				var newContentCollectionName = $('#neos-createcontentcollection-input').val();
				if (newContentCollectionName === '') {
					Notification.error('You need to give a name for the new content collection.');
				} else {
					that._createContentCollection(newContentCollectionName);
				}
			});
		}.on('activateOnPageLoad'),

		_createContentCollection: function(contentCollectionName) {
			var pageNodePath = $('#neos-page-metainformation').attr('about');
			ContentModule.showPageLoader();
			window.TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.create(
				pageNodePath,
				{
					nodeType: 'TYPO3.Neos:ContentCollection',
					nodeName: contentCollectionName
				},
				'into',
				function (result) {
					if (result.success == true) {
						$('#neos-createcontentcollection-input').val('');
						ContentModule.reloadPage();
					}
				}
			);
		},

		_reloadPage: function(wireframeModeActivated) {
			if (typeof window.TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				ContentModule.showPageLoader();
				window.TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.wireframeMode': wireframeModeActivated}, function() {
					window.location.reload(false);
				});
			}
		},
		_reloadPageWhenWireframeModeIsActivated: function() {
			this._reloadPage(true);
		}.on('activate'),
		_reloadPageWhenWireframeModeIsDeactivated: function() {
			this._reloadPage(false);
		}.on('deactivate')
	});
});