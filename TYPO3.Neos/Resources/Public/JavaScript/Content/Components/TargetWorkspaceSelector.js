/**
 * Target workspace selector
 */
define(
  [
    'emberjs',
    'Library/jquery-with-dependencies',
    'Content/Model/NodeSelection',
    './TargetWorkspaceController',
    'Content/Model/PublishableNodes',
    './DirtyWorkspaceDialog',
    'text!./TargetWorkspaceSelector.html'
  ], function (Ember,
               $,
               NodeSelection,
               TargetWorkspaceController,
               PublishableNodes,
               DirtyWorkspaceDialog,
               template) {
    return Ember.View.extend({
      elementId: 'neos-target-workspace-selector',
      classNameBindings: ['open:neos-open'],
      template: Ember.Handlebars.compile(template),

      controller: TargetWorkspaceController,
      nodeSelection: NodeSelection,
      publishableNodes: PublishableNodes,

      dirtyWorkspaceDialog: null,

      /**
       * General initialization of this view
       */
      init: function () {
        this._super();
        this._initialize();
        this.get('controller')._loadConfiguration();
      },

      /**
       * (Re-)initialize the target workspace selector
       */
      _initialize: function () {
        var that = this;

        Ember.run.next(this, function () {
          var $publishMenu = $('#neos-publish-menu');
          that.set('controller.targetWorkspaceLabel', this.get('controller.targetWorkspace.label'));
          that.$('select').select2('destroy').select2({
            maximumSelectionSize: 1,
            minimumResultsForSearch: 10,
            dropdownCssClass: 'neos-select2-large neos-target-workspace-selector',
            escapeMarkup: function (markup) {
              return markup;
            }
          }).on('select2-selecting', function (event) {
            if (that.get('publishableNodes.numberOfWorkspaceWidePublishableNodes') > 0) {
              if (!that.get('dirtyWorkspaceDialog') || that.get('dirtyWorkspaceDialog').state === 'destroying') {
                that.$('select').select2('close');
                that.set('dirtyWorkspaceDialog', DirtyWorkspaceDialog.create());
              }
              event.preventDefault();
            }
          }).on('change', function (event) {
            if (!that.get('controller.workspaceRebasePending')) {
              $publishMenu.removeClass('neos-open open');
              that.get('controller').setTargetWorkspace(event.val);
            }
          }).select2('data', {
            id: that.get('controller.targetWorkspace.name'),
            text: that.get('controller.targetWorkspace.label')
          });
          $('.neos-dropdown-toggle', $publishMenu).off('click.target-workspace-selector').on('click.target-workspace-selector', function() {
            if (!$publishMenu.hasClass('neos-open')) {
              that.$('select').select2('close');
            }
          });
        });
      }.observes('controller.workspaces')

    });
  }
);
