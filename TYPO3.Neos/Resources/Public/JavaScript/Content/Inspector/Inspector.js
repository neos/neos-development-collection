/**
 * Inspector
 */
define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'text!./Inspector.html',
	'text!./InspectorBreadcrumb.html',
	'text!./InspectorUnappliedChangesDialog.html',
	'../Components/Button',
	'require'
], function(
	$,
	Ember,
	template,
	breadcrumbTemplate,
	unappliedChangesDialogTemplate,
	Button,
	require
) {
	var BreadcrumbItem = Ember.View.extend({
		tagName: 'a',
		href: '#',
		template: Ember.Handlebars.compile('{{view.item.nodeTypeSchema.ui.label}} {{#if view.item.status}}<span class="neos-breadcrumbitem-status">({{view.item.status}})</span>{{/if}}'),
		click: function(event) {
			event.preventDefault();
			var item = this.get('item');
			T3.Content.Model.NodeSelection.selectNode(item);
		}
	});

	var Breadcrumb = Ember.View.extend({
		tagName: 'div',
		classNames: ['neos-breadcrumb'],
		template: Ember.Handlebars.compile(breadcrumbTemplate),
		BreadcrumbItem: BreadcrumbItem,
		nodes: function() {
			return this.get('content').toArray().reverse();
		}.property('content.@each')
     });

	// Make the inspector panels collapsible
	var ToggleInspectorPanelHeadline = Ember.View.extend({
		tagName: 'div',
		_collapsed: false,
		_nodeType: '',
		_automaticallyCollapsed: false,

		didInsertElement: function() {
			var selectedNode = T3.Content.Model.NodeSelection.get('selectedNode'),
				nodeType = (selectedNode.$element ? selectedNode.$element.attr('typeof').replace(/\./g,'_') : 'ALOHA'),
				collapsed = T3.Content.Controller.Inspector.get('configuration.' + nodeType + '.' + this.get('content.group'));

			this.set('_nodeType', nodeType);
			if (collapsed) {
				this.$().next().hide();
				this.set('_collapsed', true);
				this.set('_automaticallyCollapsed', true);
			}
		},

		click: function() {
			this.toggleCollapsed();
		},

		toggleCollapsed: function() {
			this.set('_collapsed', !this.get('_collapsed'));
			if (!T3.Content.Controller.Inspector.get('configuration.' + this.get('_nodeType'))) {
				T3.Content.Controller.Inspector.set('configuration.' + this.get('_nodeType'), {});
			}
			T3.Content.Controller.Inspector.set('configuration.' + this.get('_nodeType') + '.' + this.content.group, this.get('_collapsed'));
			Ember.propertyDidChange(T3.Content.Controller.Inspector, 'configuration');
		},

		_onCollapsedChange: function() {
			var $content = this.$().next();
			if (this.get('_collapsed') === true) {
				$content.slideUp(200);
			} else {
				$content.slideDown(200);
			}
		}.observes('_collapsed')
	});
	var PropertyEditor = Ember.ContainerView.extend({
		propertyDefinition: null,

		render: function() {
			var that = this;
			var typeDefinition = T3.Configuration.UserInterface[this.propertyDefinition.type];
			Ember.assert('Type defaults for "' + this.propertyDefinition.type + '" not found!', !!typeDefinition);

			var editorClassName = Ember.get(this.propertyDefinition, 'ui.inspector.editor') || typeDefinition.editor;
			Ember.assert('Editor class name for property "' + this.propertyDefinition.key + '" not found.', editorClassName);

			var editorOptions = $.extend(
				{
					valueBinding: 'T3.Content.Controller.Inspector.nodeProperties.' + this.propertyDefinition.key,
					elementId: this.propertyDefinition.elementId
				},
				typeDefinition.editorOptions || {},
				Ember.get(this.propertyDefinition, 'ui.inspector.editorOptions') || {}
			);

			require([editorClassName], function(editorClass) {
				Ember.run(function() {
					if (!that.isDestroyed) {
						// It might happen that the editor was deselected before the require() call completed; so we
						// need to check again whether the view has been destroyed in the meantime.
						var editor = editorClass.create(editorOptions);
						that.set('currentView', editor);
					}
				});
			});
		}
	});

	var UnappliedChangesDialog = Ember.View.extend({
		classNames: ['inspector-dialog'],
		template: Ember.Handlebars.compile(unappliedChangesDialogTemplate),
		cancel: function() {
			this.destroy();
		},
		apply: function() {
			T3.Content.Controller.Inspector.apply();
			this.destroy();
		},
		dontApply: function() {
			T3.Content.Controller.Inspector.revert();
			this.destroy();
		}
	});

	/**
	 * The Inspector is displayed on the right side of the page.
	 *
	 * Furthermore, it contains *Editors*
	 */
	return Ember.View.extend({
		elementId: 'neos-inspector',
		classNames: ['neos-inspector'],

		template: Ember.Handlebars.compile(template),
		Button: Button,
		ToggleInspectorPanelHeadline: ToggleInspectorPanelHeadline,
		Breadcrumb: Breadcrumb,
		PropertyEditor: PropertyEditor,

		/**
		 * When we are in edit mode, the click protection layer is intercepting
		 * every click outside the Inspector.
		 */
		$clickProtectionLayer: null,

		/**
		 * When pressing Enter inside a property, we apply and leave the edit mode
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				T3.Content.Controller.Inspector.apply();
				return false;
			}
		},

		/**
		 * When the editors have been modified, we add / remove the click
		 * protection layer.
		 */
		_onModifiedChange: function() {
			var zIndex;
			if (T3.Content.Controller.Inspector.get('_modified')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div id="neos-inspector-clickprotection" />').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(this._showUnappliedDialog);
				$('body').append(this.$clickProtectionLayer);
			} else {
				this.$clickProtectionLayer.remove();
			}
		}.observes('T3.Content.Controller.Inspector._modified'),

		/**
		 * When clicking the click protection, we show a dialog
		 */
		_showUnappliedDialog: function() {
			UnappliedChangesDialog.create().appendTo('#neos-application');
		}
	});
});