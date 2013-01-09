/**
 */
define(
	[
		'jquery',
		'vie/instance',
		'text!neos/templates/content/ui/contentelementHandles.html',
		'neos/content/ui/elements/new-contentelement-popover-content'
	],
	function ($, vieInstance, template, ContentElementPopoverContent) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/contentelement-handles');

		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),

			_element: null,

			$newAfterPopoverContent: null,

			_entityCollectionIndex: null,

			_collection: null,

			popoverPosition: 'right',

			_nodePath: null,

			_pasteInProgress: false,

			_thisElementStartedCut: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) return false;

				return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath').cacheable(),

			_thisElementStartedCopy: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) return false;

				return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath').cacheable(),

			didInsertElement: function() {
				var that = this;
				var subject = vieInstance.service('rdfa').getElementSubject(this.get('_element'));
				this.set('_nodePath', vieInstance.entities.get(subject).getSubjectUri());

				this.$newAfterPopoverContent = $('<div />', {id: this.get(Ember.GUID_KEY)});

				this.$().find('.action-new').popover({
					additionalClasses: 't3-new-contentelement-popover',
					content: this.$newAfterPopoverContent,
					preventLeft: this.get('popoverPosition') === 'left' ? false : true,
					preventRight: this.get('popoverPosition') === 'right' ? false : true,
					preventTop: this.get('popoverPosition') === 'top' ? false : true,
					preventBottom: this.get('popoverPosition') === 'bottom' ? false : true,
					positioning: 'absolute',
					zindex: 10090,
					closeEvent: function() {
						that.set('pressed', false);
					},
					openEvent: function() {
						that.onPopoverOpen.call(that);
					}
				});
			},

			remove: function() {
				T3.Content.Controller.NodeActions.remove(vieInstance.entities.get(this.get('_nodePath')), this.get('_element'), this.$('.action-remove').first());
			},

			cut: function() {
				T3.Content.Controller.NodeActions.cut(this.get('_nodePath'));
			},

			copy: function() {
				T3.Content.Controller.NodeActions.copy(this.get('_nodePath'));
			},

			pasteAfter: function() {
				T3.Content.Controller.NodeActions.pasteAfter(this.get('_nodePath'));
				this.set('_pasteInProgress', true);
			},

			newAfter: function() {
				var that = this;
				this.$().find('.action-new').trigger('showPopover');
			},

			onPopoverOpen: function() {
				var groups = {};

				_.each(this.get('_collection').options.definition.range, function(contentType) {
					var type = this.get('_collection').options.vie.types.get(contentType);
					type.metadata.contentType = type.id.substring(1, type.id.length - 1).replace(T3.ContentModule.TYPO3_NAMESPACE, '');

					if (type.metadata.group) {
						if (!groups[type.metadata.group]) {
							groups[type.metadata.group] = {
								name: type.metadata.group,
								children: []
							};
						}
						groups[type.metadata.group].children.push(type.metadata);
					}
				}, this);

					// Make the data object an array for usage in #each helper
				var data = []

				T3.Configuration.contentTypeGroups.forEach(function(groupName) {
					if (groups[groupName]) {
						data.push(groups[groupName]);
					}
				});


				ContentElementPopoverContent.create({
					_options: this.get('_collection').options,
					_index: this.get('_entityCollectionIndex'),
					data: data
				}).replaceIn(this.$newAfterPopoverContent);
			},

			willDestroyElement: function() {
				this.$().find('.action-new').trigger('hidePopover');
			},

			_showRemove: true,
			_showCut: true,
			_showCopy: true

		});
	}
);