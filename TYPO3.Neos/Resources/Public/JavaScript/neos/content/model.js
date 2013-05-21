/**
 * T3.Content.Model
 *
 * Contains the main models underlying the content module UI.
 *
 * The most central model is the "Block" model, which shadows the Aloha Block model.
 */

define(
	['Library/jquery-with-dependencies', 'Library/underscore', 'emberjs', 'vie/instance', 'vie/entity'],
	function($, _, Ember, vie, EntityWrapper) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/model');

		var T3 = window.T3 || {};
		if (typeof T3.Content === 'undefined') {
			T3.Content = {};
		}
		T3.Content.Model = {};

		/**
		 * T3.Content.Model.NodeSelection
		 *
		 * Contains the currently selected node proxy with parents.
		 *
		 * This model is the one most listened to, as when the node selection changes, the UI
		 * is responding to that.
		 */
		var NodeSelection = Ember.Object.extend({
			nodes: [],

			/**
			 *
			 */
			initialize: function() {
				vie.entities.reset();
				vie.load({element: $('body')}).from('rdfa').execute();

					// Update the selection on initialize, such that the current Page is added to the breadcrumb
				this.updateSelection();
			},

			/**
			 * Update the selection from a selected content element.
			 * If we have a node activated, we add the CSS class "neos-contentelement-selected"
			 * to the body so that we can modify the appearance of the content element editing handles.
			 */
			updateSelection: function($element) {
					// Do not update the selection if the element is already selected
				if ($element && $element.hasClass('neos-contentelement-active')) {
					return;
				}

				var nodes = [],
					that = this;

					// Remove active class from all previously active nodes (content elements and contentcollection)
				$('.neos-contentelement-active').removeClass('neos-contentelement-active');

					// TODO Check if we need that
				if (this._updating) {
					return;
				}
				this._updating = true;

				if ($element !== undefined) {
						// Add active class to selected content element
					if ($element.is('.neos-contentcollection')) {
							// If we are inside a contentcollection, we want to mark the outer element as active; as this also
							// contains the contentcollection handles.
						$element.parent().addClass('neos-contentelement-active');
					} else {
						$element.addClass('neos-contentelement-active');
					}

					this.addNodeByElement(nodes, $element);
					$element.parents('.neos-contentelement[about], .neos-contentcollection[about]').each(function() {
						that.addNodeByElement(nodes, this);
					});

						// Add class to body that we have a content element selected
					$('body').addClass('neos-contentelement-selected');
				} else {
					$('body').removeClass('neos-contentelement-selected');
				}

					// add page node
				if (!$element || !$element.is('#neos-page-metainformation')) {
					this.addNodeByElement(nodes, $('#neos-page-metainformation'));
				}

				nodes = nodes.reverse();
				this.set('nodes', nodes);

				this._updating = false;
			},

			/**
			 *
			 * @param nodes
			 * @param $element
			 */
			addNodeByElement: function(nodes, $element) {
				if ($element !== undefined) {
					var nodeProxy = this._createEntityWrapper($element);
					if (nodeProxy) {
						nodes.push(nodeProxy);
					}
				}
			},

			_entitiesBySubject: {},

			_createEntityWrapper: function($element) {
				var subject = vie.service('rdfa').getElementSubject($element);

				if (!this._entitiesBySubject[subject]) {
					var entity = vie.entities.get(subject);
					if (entity === undefined) {
						return;
					}

					this._entitiesBySubject[subject] = EntityWrapper.create({
						_vieEntity: entity
					});
				}

				return this._entitiesBySubject[subject];
			},

			selectedNode: function() {
				var nodes = this.get('nodes');
				return nodes.length > 0 ? _.last(nodes) : null;
			}.property('nodes'),

			selectedNodeSchema: function() {
				var selectedNode = this.get('selectedNode');
				if (!selectedNode) return;
				return selectedNode.get('nodeTypeSchema');
			}.property('selectedNode'),

			selectNode: function(node) {
				this.updateSelection(node.get('$element'));
			}
		}).create();

		var PublishableNodes = Ember.Object.extend({
			publishableEntitySubjects: [],

			noChanges: function() {
				return this.get('publishableEntitySubjects.length') == 0;
			}.property('publishableEntitySubjects.length'),

			initialize: function() {
				vie.entities.on('change', this._updatePublishableEntities, this);
				this._updatePublishableEntities();
			},

			_updatePublishableEntities: function() {
				var publishableEntitySubjects = [];
				vie.entities.forEach(function(entity) {
					if (this._isEntityPublishable(entity)) {
						publishableEntitySubjects.push(entity.id);
					}
				}, this);

				this.set('publishableEntitySubjects', publishableEntitySubjects);
			},

			/**
			 * Check whether the entity is publishable or not. Currently, everything
			 * which is not in the live workspace is publishable.
			 */
			_isEntityPublishable: function(entity) {
				var attributes = EntityWrapper.extractAttributesFromVieEntity(entity);
				return attributes['__workspacename'] !== 'live';
			},

			/**
			 * Publish all blocks which are unsaved *and* on current page.
			 */
			publishAll: function() {
				T3.Content.Controller.ServerConnection.sendAllToServer(
					this.get('publishableEntitySubjects'),
					function(subject) {
						var entity = vie.entities.get(subject);
						return [entity.fromReference(subject), 'live'];
					},
					TYPO3_Neos_Service_ExtDirect_V1_Controller_WorkspaceController.publishNode,
					null,
					function(subject) {
						var entity = vie.entities.get(subject);
						entity.set('typo3:__workspacename', 'live');
					}
				);
			}
		}).create();

		T3.Content.Model = {
			PublishableNodes: PublishableNodes,
			NodeSelection: NodeSelection
		}
		window.T3 = T3;
	}
);