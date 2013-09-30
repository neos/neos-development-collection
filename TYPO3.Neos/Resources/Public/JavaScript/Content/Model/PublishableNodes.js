/**
 * PublishableNodes
 *
 * Contains the currently publishable (proxy) nodes.
 */
define(
[
	'emberjs',
	'vie/instance',
	'vie/entity'
], function(
	Ember,
	vie,
	EntityWrapper
) {
	return Ember.Object.extend({
		publishableEntitySubjects: [],

		noChanges: function() {
			return this.get('publishableEntitySubjects').length === 0;
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
			return attributes.__workspacename !== 'live';
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
});