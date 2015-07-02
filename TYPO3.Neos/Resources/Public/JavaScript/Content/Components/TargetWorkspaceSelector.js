/**
 * Target workspace selector
 */
define(
	[
		'emberjs',
		'Content/Model/NodeSelection',
		'./TargetWorkspaceController',
		'text!./TargetWorkspaceSelector.html'
	], function(
		Ember,
		NodeSelection,
		TargetWorkspaceController,
		template
	) {
		return Ember.View.extend({
			elementId: 'neos-target-workspace-selector',
			classNameBindings: ['open:neos-open'],
			template: Ember.Handlebars.compile(template),

			controller: TargetWorkspaceController,
			nodeSelection: NodeSelection,

			/**
			 * General initialization of this view
			 */
			init: function() {
				this._super();
				this._initialize();
				this.get('controller')._loadConfiguration();
			},

			/**
			 * (Re-)initialize the target workspace selector
			 */
			_initialize: function() {
				var that = this;
				Ember.run.next(this, function() {
					that.set('controller.targetWorkspaceLabel', this.get('controller.targetWorkspace.name'));
					that.$('select').select2('destroy').select2({
						maximumSelectionSize: 1,
						minimumResultsForSearch: 10,
						dropdownCssClass: 'neos-select2-large neos-target-workspace-selector'
					}).on('change', function(event) {
						if (!that.get('controller.workspaceRebasePending')) {
							$('#neos-publish-menu').removeClass('neos-open open');
							that.get('controller').setTargetWorkspace(event.val);
						}
					}).select2('data', {id: that.get('controller.targetWorkspace.name'), text: that.get('controller.targetWorkspace.name')})
				});
			}.observes('controller.workspaces')

		});
	});
