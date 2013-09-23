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
	'../Components/ToggleButton',
	'require'
], function(
	$,
	Ember,
	template,
	breadcrumbTemplate,
	unappliedChangesDialogTemplate,
	Button,
	ToggleButton,
	require
) {
	var BreadcrumbItem = Ember.View.extend({
		tagName: 'a',
		href: '#',
		click: function(e) {
			e.preventDefault();
			T3.Content.Model.NodeSelection.selectNode(this.get('content'));
		}
	});

	var Breadcrumb = Ember.View.extend({
		tagName: 'div',
		classNames: ['neos-content-breadcrumb'],
		classNameBindings: ['open:neos-open'],
		template: Ember.Handlebars.compile(breadcrumbTemplate),
		BreadcrumbItem: BreadcrumbItem,
		open: false,

		nodes: function() {
			this.set('open', false);
			return this.get('content').toArray().reverse();
		}.property('content.@each'),

		click: function() {
			this.set('open', !this.get('open'));
		}
	});

	var PropertyEditor = Ember.ContainerView.extend({
		propertyDefinition: null,
		value: null,
		isModified: false,
		hasValidationErrors: false,
		classNameBindings: ['isModified:modified', 'hasValidationErrors:neos-error'],

		_valueDidChange: function() {
			if (T3.Content.Controller.Inspector.isPropertyModified(this.get('propertyDefinition.key')) === true) {
				this.set('isModified', true);
			} else {
				this.set('isModified', false);
			}
		}.observes('value'),

		_validationErrorsDidChange: function() {
			if (this.get('isDestroyed') === true) {
				return;
			}
			var property = this.get('propertyDefinition.key'),
				validationErrors = T3.Content.Controller.Inspector.get('validationErrors.' + property) || [];
			if (validationErrors.length > 0) {
				this.set('hasValidationErrors', true);
				this.$().tooltip('destroy').tooltip({
					animation: false,
					placement: 'bottom',
					title: validationErrors[0],
					trigger: 'manual'
				}).tooltip('show');
			} else {
				this.set('hasValidationErrors', false);
				this.$().tooltip('destroy');
			}
		},

		render: function() {
			var that = this,
				propertyDefinition = this.get('propertyDefinition');
			Ember.bind(this, 'value', 'T3.Content.Controller.Inspector.nodeProperties.' + propertyDefinition.key);

			var typeDefinition = T3.Configuration.UserInterface[propertyDefinition.type];
			Ember.assert('Type defaults for "' + propertyDefinition.type + '" not found!', !!typeDefinition);

			var editorClassName = Ember.get(propertyDefinition, 'ui.inspector.editor') || typeDefinition.editor;
			Ember.assert('Editor class name for property "' + propertyDefinition.key + '" not found.', editorClassName);

			var editorOptions = $.extend(
				{
					valueBinding: 'T3.Content.Controller.Inspector.nodeProperties.' + propertyDefinition.key,
					elementId: propertyDefinition.elementId
				},
				typeDefinition.editorOptions || {},
				Ember.get(propertyDefinition, 'ui.inspector.editorOptions') || {}
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
		},

		didInsertElement: function() {
			T3.Content.Controller.Inspector.validationErrors.addObserver(this.get('propertyDefinition.key'), this, '_validationErrorsDidChange');
		}
	});

	// Make the inspector panels collapsible
	var InspectorSectionToggle = Ember.View.extend({
		tagName: 'div',
		classNameBindings: ['_collapsed:collapsed:neos-open'],
		_collapsed: false,
		_nodeType: '',
		_automaticallyCollapsed: false,

		didInsertElement: function() {
			var selectedNode = T3.Content.Model.NodeSelection.get('selectedNode'),
				nodeType = (selectedNode.$element ? selectedNode.$element.attr('typeof').replace(/\./g,'_') : 'ALOHA'),
				collapsed = T3.Content.Controller.Inspector.get('configuration.' + nodeType + '.' + this.get('group'));

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
			T3.Content.Controller.Inspector.set('configuration.' + this.get('_nodeType') + '.' + this.get('group'), this.get('_collapsed'));
			Ember.propertyDidChange(T3.Content.Controller.Inspector, 'configuration');
		},

		_onCollapsedChange: function() {
			var $content = this.$().parent().next();
			if (this.get('_collapsed') === true) {
				$content.slideUp(200);
			} else {
				$content.slideDown(200);
			}
		}.observes('_collapsed')
	});

	var InspectorSection = Ember.View.extend({
		InspectorSectionToggle: InspectorSectionToggle,
		PropertyEditor: PropertyEditor,
		_hasValidationErrors: false,

		didInsertElement: function() {
			var that = this;
			$.each(this.get('properties'), function(index, property) {
				T3.Content.Controller.Inspector.validationErrors.addObserver(property.key, that, '_validationErrorsDidChange');
			});
		},

		_validationErrorsDidChange: function() {
			if (this.get('isDestroyed') === true) {
				return;
			}
			var hasValidationErrors = false;
			$.each(this.get('properties'), function(index, property) {
				var validationErrors = T3.Content.Controller.Inspector.get('validationErrors.' + property.key) || [];
				if (validationErrors.length > 0) {
					hasValidationErrors = true;
				}
			});
			this.set('_hasValidationErrors', hasValidationErrors);
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

		template: Ember.Handlebars.compile(template),
		Button: Button,
		ToggleButton: ToggleButton,
		InspectorSection: InspectorSection,
		Breadcrumb: Breadcrumb,

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
		_modifiedDidChange: function() {
			var zIndex,
				that = this;
			if (T3.Content.Controller.Inspector.get('_modified')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div id="neos-inspector-clickprotection" />').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(function(e) {
					e.stopPropagation();
					that._showUnappliedDialog();
				});
				$('#neos-application').append(this.$clickProtectionLayer);
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