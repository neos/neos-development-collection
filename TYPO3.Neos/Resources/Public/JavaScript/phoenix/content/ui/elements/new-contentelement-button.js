/**
 */
define(
	[
		'jquery',
		'text!phoenix/templates/content/ui/newContentelementButton.html',
		'phoenix/content/ui/elements/popover-button',
		'phoenix/content/ui/elements/new-contentelement-popover-content'
	],
	function ($, template, PopoverButton, ContentElementPopoverContent) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui/elements/new-contentelement-button');

		return PopoverButton.extend({
			template: Ember.Handlebars.compile(template),

			$popoverContent: null,

			popoverPosition: 'right',

			_index: null,

			_collection: null,

			didInsertElement: function() {
				var that = this;

					// Move the button to the center of the parent content element
				this.$().css({
					left: (this.$().parents('.t3-contentelement').first().width() / 2) - (this.$().width() / 2)
				});

				this.$popoverContent = $('<div />', {id: this.get(Ember.GUID_KEY)});

				this.$().popover({
					content: this.$popoverContent,
					preventLeft: (this.get('popoverPosition')==='left' ? false : true),
					preventRight: (this.get('popoverPosition')==='right' ? false : true),
					preventTop: (this.get('popoverPosition')==='top' ? false : true),
					preventBottom: (this.get('popoverPosition')==='bottom' ? false : true),
					zindex: 10090,
					closeEvent: function() {
						that.set('pressed', false);
					},
					openEvent: function() {
						that.onPopoverOpen.call(that);
					}
				});
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
				for (var group in groups) {
					data.push(groups[group]);
				}

				ContentElementPopoverContent.create({
					_options: this.get('_collection').options,
					_index: this.get('_index'),
					data: data
				}).replaceIn(this.$popoverContent);
			},

			willDestroyElement: function() {
				this.$().trigger('hidePopover');
			}
		});
	}
);