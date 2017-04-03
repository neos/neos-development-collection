define(
[
	'Library/jquery-with-dependencies',
	'Content/Model/Node',
	'Content/Model/NodeSelection',
	'Library/backbone',
	'Shared/Endpoint/NodeEndpoint',
	'Shared/EventDispatcher',
	'Shared/Notification'
], function(
	$,
	Entity,
	NodeSelection,
	Backbone,
	NodeEndpoint,
	EventDispatcher,
	Notification
) {
	Backbone.sync = function(method, model, options) {
		var methods = {
			'create': function(model, options) {
				console.log('CREATE', arguments);
			},
			'read': function(model, options) {
				console.log('READ', arguments);
			},
			'update': function(model, options) {
				var nodeJson = this._convertModelToJson(model),
					changedAttributes = Entity.extractAttributesFromVieEntity(model, model.changed),
					method = options.render === true ? 'updateAndRender' : 'update',
					typoScriptPath = options.render === true ? model._enclosingCollectionWidget.options.model.get('typo3:__typoscriptPath') : null;

				var xhr = $.ajaxSettings.xhr();
				NodeEndpoint.set('_saveRunning', true);
				NodeEndpoint[method](nodeJson, typoScriptPath, {xhr: function() {
					return xhr;
				}}).then(
					function(result) {
						// when we save a node, it could be the case that it was in
						// live workspace beforehand, but because of some modifications,
						// is now copied into the user's workspace.
						// That's why we need to update the (possibly changed) workspace
						// name in the VIE entity.
						//
						// Furthermore, we do not want event listeners to be fired, as otherwise the content element
						// would be redrawn leading to a loss of the current editing cursor position.
						NodeEndpoint.set('_saveRunning', false);
						EventDispatcher.trigger('contentSaved');

						if (result !== undefined && (result.success === true || options.render)) {
							if (!options.render) {
								model.set('typo3:__workspaceName', result.data.workspaceNameOfNode, {silent: true});
								// The PublishableNodes are explicitly updated through the ``nodesUpdated`` event, as changes from
								// the backbone models workspace name attribute are suppressed and our entity wrapper would not notice.
								EventDispatcher.trigger('nodeUpdated');
								NodeSelection.getNode(model.id.slice(1, -1)).setAttribute('__label', result.data.labelOfNode, {silent: true});
							} else if ('_nodeType' in changedAttributes) {
								EventDispatcher.trigger('contentChanged');
							}

							NodeEndpoint.set('_lastSuccessfulTransfer', new Date());
							if (options && options.success) {
								options.success(model, result, xhr);
							}
						}
					},
					function() {
						NodeEndpoint.set('_saveRunning', false);
						require({context: 'neos'}, ['InlineEditing/Dialogs/NodeUpdateFailureDialog'], function(NodeUpdateFailureDialog) {
							NodeUpdateFailureDialog.create();
						});
					}
				).fail(function(error) {
					Notification.error('An error occurred.');
					console.error('An error occurred:', error);
				});
			},
			'delete': function(model, options) {
				console.log('DELETE', arguments);
			},
			_convertModelToJson: function(model) {
				var contextPath = model.fromReference(model.id);
				var attributes = Entity.extractAttributesFromVieEntity(model, model.changed, function(k) {
						// skip internal properties starting with __; and skip "content-collection" (which is collection-specific)
					return !( (k[0] === '_' && k[1] === '_') || k === 'content-collection');
				});
				attributes.__contextNodePath = contextPath;
				return attributes;
			}
		};

		methods[method].call(methods, model, options);
	};
});
