define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Shared/LocalStorage',
		'../Components/ToggleButton',
		'Shared/ResourceCache',
		'text!./MenuPanel.html'
	], function(Ember, $, LocalStorage, ToggleButton, ResourceCache, template) {

		return Ember.View.extend({
			elementId: 'neos-menu-panel',
			template: Ember.Handlebars.compile(template),
			ToggleMenuPanelHeadline: Ember.View.extend({
				tagName: 'div',
				classNameBindings: ['_collapsed:collapsed:open'],
				_collapsed: false,

				didInsertElement: function() {
					var menuGroup = this.get('group'),
						parentView = this.get('_parentView._parentView._parentView._parentView') || this.get('_parentView'),
						collapsed = parentView.get('configuration.' + menuGroup);

					if (typeof menuGroup !== 'undefined') {
						if (collapsed) {
							this.$().next().hide();
							this.set('_collapsed', true);
						}
					}
				},

				click: function() {
					this.toggleCollapsed();
				},

				toggleCollapsed: function() {
					var menuGroup = this.get('group'),
						parentView = this.get('_parentView._parentView._parentView._parentView') || this.get('_parentView');

					this.set('_collapsed', !this.get('_collapsed'));
					if (!parentView.get('configuration.' + menuGroup)) {
						parentView.set('configuration.' + menuGroup, {});
					}
					parentView.set('configuration.' + menuGroup, this.get('_collapsed'));
					Ember.propertyDidChange(parentView, 'configuration');
				},

				_onCollapsedChange: function() {
					var $content = this.$().next();
					if (this.get('_collapsed') === true) {
						$content.slideUp(200);
					} else {
						$content.slideDown(200);
					}
				}.observes('_collapsed')
			}),
			items: [],
			configuration: null,

			init: function() {
				var that = this;
				$.when(ResourceCache.get(T3.Configuration.MenuDataUri)).done(function(dataString) {
					var data = JSON.parse(dataString);
					that.set('items', data);
				}).fail(function(xhr, status, error) {
					console.error('Error loading menu data.', xhr, status, error);
				});

				this.set('configuration', LocalStorage.getItem('menuConfiguration') || {});
				Ember.addObserver(this, 'configuration', function() {
					var configuration = this.get('configuration');
					if ($.isEmptyObject(configuration) === false) {
						LocalStorage.setItem('menuConfiguration', configuration);
					}
				});
			},

			activeItem: function() {
				var that = this;
				if (location.pathname.substr(0, 6) === '/neos/') {
					$.each(this.get('items.moduleList'), function(index, value) {
						if (location.pathname.indexOf(value.modulePath) !== -1) {
							that.set('items.moduleList.' + index + '.active', true);
						}
					});
				} else {
					$.each(this.get('items.siteList'), function(index, value) {
						if (value.uri.indexOf(location.hostname) !== -1) {
							that.set('items.siteList.' + index + '.active', true);
						}
					});
				}
			}.observes('items')
		});
	}
);