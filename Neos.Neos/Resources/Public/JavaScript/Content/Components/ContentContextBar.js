/**
 * Context context bar
 */
define(
  [
    'emberjs',
    './ContextBar',
    './ContentDimensionSelector',
    './ContentDimensionController',
    '../FullScreenController',
    '../Application',
    './TargetWorkspaceController',
    'Shared/Configuration',
    'text!./ContentContextBar.html'
  ], function (Ember,
               ContextBar,
               ContentDimensionSelector,
               ContentDimensionController,
               FullScreenController,
               ContentModule,
               TargetWorkspaceController,
               Configuration,
               template) {
    return ContextBar.extend({
      classNames: 'neos-content-context-bar',
      template: Ember.Handlebars.compile(template),
      ContentDimensionSelector: ContentDimensionSelector,
      contentDimensionController: ContentDimensionController,
      fullScreenController: FullScreenController,
      targetWorkspaceController: TargetWorkspaceController,
      Configuration: Configuration,

      /**
       * Update the preview uri when the page is loaded (happens when navigating, switching
       * target workspace or changing content dimensions).
       */
      init: function () {
        var that = this;
        ContentModule.on('pageLoaded', function () {
          that.updatePreviewUri();
        });
      },

      /**
       * Update the preview uri
       */
      updatePreviewUri: function () {
        var targetWorkspaceName = this.get('targetWorkspaceController.targetWorkspace.name');
        if (targetWorkspaceName === 'live') {
          this.set('previewUri', location.href.replace(/@[A-Za-z0-9;&,\-_=]+/g, ''));
        } else {
          this.set('previewUri', location.href.replace(/@[A-Za-z0-9;&,\-_=]+/g, '@' + targetWorkspaceName));
        }
      }.observes('targetWorkspaceController.targetWorkspace', 'contentDimensionController.selectedDimensions'),

      /**
       * Show tooltip for inserted elements
       */
      didInsertElement: function() {
        this.$('[data-neos-tooltip]').tooltip();
      }
    })
  });
