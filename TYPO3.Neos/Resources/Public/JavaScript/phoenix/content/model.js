/**
 * T3.Content.Model
 *
 * Contains the main models underlying the content module UI.
 *
 * The most central model is the "Block" model, which shadows the Aloha Block model.
 */

define(
	['jquery', 'vie/instance', 'vie/entity'],
	function($, vie, EntityWrapper) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/model');

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
		var NodeSelection = Ember.Object.create({
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
			 * If we have a node activated, we add the CSS class "t3-contentelement-selected"
			 * to the body so that we can modify the appearance of the content element editing handles.
			 */
			updateSelection: function($element) {
				var nodes = [],
					that = this;

					// Remove active class from all previously active nodes (content elements and sections)
				$('.t3-contentelement[about], .t3-contentsection[about]').removeClass('t3-contentelement-active');

					// TODO Check if we need that
				if (this._updating) {
					return;
				}
				this._updating = true;

				if ($element !== undefined) {
						// Add active class to selected content element
					$element.addClass('t3-contentelement-active');

					this.addNodeByElement(nodes, $element);
					$element.parents('.t3-contentelement[about], .t3-contentsection[about]').each(function() {
						that.addNodeByElement(nodes, this);
					});

						// Add class to body that we have a content element selected
					$('body').addClass('t3-contentelement-selected');
				} else {
					$('body').removeClass('t3-contentelement-selected');
				}

					// add page node
				if (!$element || !$element.is('#t3-page-metainformation')) {
					this.addNodeByElement(nodes, $('#t3-page-metainformation'));
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
				var subject = vie.service("rdfa").getElementSubject($element);

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
			}.property('nodes').cacheable(),


			selectedNodeSchema: function() {
				var selectedNode = this.get('selectedNode');
				if (!selectedNode) return;
				return selectedNode.get('contentTypeSchema');
			}.property('selectedNode').cacheable(),

			selectNode: function(node) {
				this.updateSelection(node.get('$element'));
			}
		});

		var PublishableNodes = Ember.Object.create({

			publishableEntitySubjects: [],

			noChanges: function() {
				return this.getPath('publishableEntitySubjects.length') == 0;
			}.property('publishableEntitySubjects').cacheable(),

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
				this.get('publishableEntitySubjects').forEach(function(subject) {
					var entity = vie.entities.get(subject);
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.publishNode(entity.fromReference(subject), 'live', function() {
						entity.set('typo3:__workspacename', 'live');
					});
				});
			}
		});

		T3.Content.Model = {
			PublishableNodes: PublishableNodes,
			NodeSelection: NodeSelection
		}
		window.T3 = T3;
	}
);