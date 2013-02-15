define(['vie/entity', 'neos/content/controller', 'neos/content/model', 'backbone'], function(Entity) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('storage');

	Backbone.sync = function(method, model, options) {
		var methods = {
			'create': function(model, options) {
				console.log("CREATE", arguments);
			},
			'read': function(model, options) {
				console.log("READ", arguments);
			},
			'update': function(model, options) {
				var nodeJson = this._convertModelToJson(model);

				T3.Content.Controller.ServerConnection.set('_saveRunning', true);
				window.TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.update(nodeJson, function(result) {
						// when we save a node, it could be the case that it was in
						// live workspace beforehand, but because of some modifications,
						// is now copied into the user's workspace.
						// That's why we need to update the (possibly changed) workspace
						// name in the VIE entity.
						//
						// Furthermore, we do not want event listeners to be fired, as otherwise the contentelement
						// would be redrawn leading to a loss of the current editing cursor position.
						//
						// The PublishableNodes are explicitly uppdated, as changes from the backbone models
						// workspacename attribute are suppressed and our entity wrapper would not notice.
					T3.Content.Controller.ServerConnection.set('_saveRunning', false);

					if (result !== undefined) {
						model.set('typo3:__workspacename', result.data.workspaceNameOfNode, {silent: true});
						T3.Content.Controller.ServerConnection.set('_lastSuccessfulTransfer', new Date());
						T3.Content.Model.PublishableNodes._updatePublishableEntities();
						if (options && options.success) {
							options.success(model, result);
						}
					}
				});
			},
			'delete': function(model, options) {
				console.log("DELETE", arguments);
			},
			_convertModelToJson: function(model) {
				var contextPath = model.fromReference(model.id);
				var attributes = Entity.extractAttributesFromVieEntity(model, function filter(k, v) {
						// skip internal properties starting with __
					if (k[0] === '_' && k[1] === '_') {
						return false;
					}
					return true;
				});
				attributes['__contextNodePath'] = contextPath;
				return attributes;
			}
		};

		methods[method].call(methods, model, options);
	};
});