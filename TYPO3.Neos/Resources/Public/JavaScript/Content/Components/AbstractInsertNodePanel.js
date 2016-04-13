define([
	'emberjs',
	'Shared/AbstractModal',
	'Shared/Configuration',
	'Shared/I18n',
	'Shared/LocalStorage',
	'./HelpMessage',
	'text!./AbstractInsertNodePanel.html'
], function(
	Ember,
	AbstractModal,
	Configuration,
	I18n,
	LocalStorage,
	HelpMessage,
	template
) {
	return AbstractModal.extend({
		template: Ember.Handlebars.compile(template),
		configuration: null,
		insertNode: Ember.required,
		HelpMessage: HelpMessage,
		_position: null,
		_positionIconClass: function() {
			switch (this.get('_position')) {
				case 'into':
					return 'icon-long-arrow-right';
				case 'before':
					return 'icon-level-up';
				case 'after':
					return 'icon-level-down';
			}
			return '';
		}.property('_position'),

		init: function() {
			this._super();
			this.set('configuration', LocalStorage.getItem('insertNodePanelConfiguration') || {});
			Ember.addObserver(this, 'configuration', function() {
				var configuration = this.get('configuration');
				LocalStorage.setItem('insertNodePanelConfiguration', configuration);
			});
		},

		didInsertElement: function() {
			this.$().find('[data-neos-tooltip]').tooltip();
		},

		ToggleNodeTypeGroup: Ember.View.extend({
			tagName: 'a',
			href: '#',
			nodeTypeGroup: Ember.required,
			attributeBindings: ['href'],
			classNames: ['neos-modal-collapse-group'],
			classNameBindings: ['nodeTypeGroup.collapsed:neos-collapsed:neos-open'],

			didInsertElement: function() {
				if (this.get('nodeTypeGroup.collapsed') === true) {
					this.$().parent().next().slideUp(0);
				}
			},

			_collapsedDidChange: function() {
				var $content = this.$().parent().next();
				if (this.get('nodeTypeGroup.collapsed') === true) {
					$content.slideUp(200);
				} else {
					$content.slideDown(200);
				}
			}.observes('nodeTypeGroup.collapsed'),

			click: function(e) {
				e.preventDefault();
				this.get('nodeTypeGroup').toggleProperty('collapsed');
			}
		}),

		nodeTypeGroups: function() {
			var that = this,
				nodeTypeGroups = Ember.A();

			Configuration.get('nodeTypes.groups').forEach(function(group) {
				nodeTypeGroups.pushObject(Ember.Object.extend({
					name: group.name,
					label: I18n.translate(group.label),
					position: group.position,
					collapsed: group.collapsed,
					nodeTypes: Ember.A(),

					init: function() {
						var collapsed = that.get('configuration.' + this.get('name'));
						if (typeof collapsed === 'boolean') {
							this.set('collapsed', collapsed);
						}
					},

					_collapsedDidChange: function() {
						that.set('configuration.' + this.get('name'), this.get('collapsed'));
						that.propertyDidChange('configuration');
					}.observes('collapsed'),

					sortedNodeTypes: function() {
						return this.get('nodeTypes').sort(function(a, b) {
							return (Ember.get(a, 'position') || 9999) - (Ember.get(b, 'position') || 9999);
						});
					}.property('nodeTypes.@each')
				}).create());
			});

			return nodeTypeGroups;
		}.property()
	});
});
