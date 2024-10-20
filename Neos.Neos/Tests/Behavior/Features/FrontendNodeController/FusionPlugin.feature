@flowEntities
Feature: Tests for sub-request on the frontend node controller in case of the "Neos.Neos:Plugins" Fusion prototype

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Content': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType':
      superTypes:
        Neos.Neos:Document: true
    'Neos.Neos:Plugin':
      superTypes:
        'Neos.Neos:Content': true
      abstract: true

    'Neos.Neos:Content.MyPlugin':
      superTypes:
        'Neos.Neos:Plugin': true
      properties:
        'myPluginProp':
          type: string
          defaultValue: ''
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues                        | nodeName |
      | a               | root                  | Neos.Neos:Site              | {"title": "Node a"}                          | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType | {"uriPathSegment": "a1", "title": "Node a1"} | a1       |
      | a1a             | a1                    | Neos.Neos:Content.MyPlugin  | {"myPluginProp": "hello from the node"}      | a1a      |
    And A site exists for node name "a" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'a':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """

  Scenario: Default output
    When I declare the following controller 'Vendor\Site\Controller\MyPluginController':
    """php
    // <?php
    namespace Vendor\Site\Controller;

    use Neos\Flow\Mvc\Controller\ActionController;
    use Neos\FluidAdaptor\View\StandaloneView;

    class MyPluginController extends ActionController
    {
        protected $defaultViewObjectName = StandaloneView::class;

        public function listAction()
        {
            $myPluginProp = $this->request->getInternalArgument('__myPluginProp');

            $uri = $this->uriBuilder->uriFor('show', ['item' => 'hello to other'], 'MyPlugin', 'Vendor.Site');

            return "list\nmyPluginProp: $myPluginProp\nuri to show: $uri";
        }

        public function showAction() // string $propToOtherAction doesnt work because the object is not proxied
        {
            $myPluginProp = $this->request->getInternalArgument('__myPluginProp');
            $propToOtherAction = $this->request->getArgument('item');
            return "details\nmyPluginProp: $myPluginProp\nargument: $propToOtherAction";
        }

        public static function getPublicActionMethods($objectManager)
        {
            return array_fill_keys(get_class_methods(get_called_class()), true);
        }
    }
    """

    When the sites Fusion code is:
    """fusion
    prototype(Neos.Neos:Test.DocumentType) < prototype(Neos.Fusion:Component) {

      renderer = afx`
        title: {node.properties.title}{String.chr(10)}
        children: <Neos.Neos:ContentCase @context.node={q(node).children().get(0)} />
      `
    }

    prototype(Neos.Neos:Content.MyPlugin) < prototype(Neos.Neos:Plugin) {
      package = 'Vendor.Site'
      controller = 'MyPlugin'
      action = 'list'

      myPluginProp = ${q(node).property('myPluginProp')}
    }
    """

    When I dispatch the following request "/a1"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    title: Node a1
    children: list
    myPluginProp: hello from the node
    uri to show: /a1?--neos_neos-content_myplugin%5B%40package%5D=vendor.site&--neos_neos-content_myplugin%5B%40controller%5D=myplugin&--neos_neos-content_myplugin%5B%40action%5D=show&--neos_neos-content_myplugin%5B%40format%5D=html&--neos_neos-content_myplugin%5Bitem%5D=hello+to+other
    """

    When I dispatch the following request "/a1?--neos_neos-content_myplugin%5B%40package%5D=vendor.site&--neos_neos-content_myplugin%5B%40controller%5D=myplugin&--neos_neos-content_myplugin%5B%40action%5D=show&--neos_neos-content_myplugin%5B%40format%5D=html&--neos_neos-content_myplugin%5Bitem%5D=hello+to+other"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    title: Node a1
    children: details
    myPluginProp: hello from the node
    argument: hello to other
    """
