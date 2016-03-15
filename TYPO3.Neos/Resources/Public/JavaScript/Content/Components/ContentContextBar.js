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
       * Computed property of preview uri
       */
      previewUri: function() {
        var targetWorkspaceName = this.get('targetWorkspaceController.targetWorkspace.name');
        if (targetWorkspaceName === 'live') {
          return location.href.replace(/@[A-Za-z0-9;&,\-_=]+/g, '');
        } else {
          return location.href.replace(/@[A-Za-z0-9;&,\-_=]+/g, '@' + targetWorkspaceName);
        }
      }.property('targetWorkspaceController.targetWorkspace', 'contentDimensionController.selectedDimensions')
    });
  });
