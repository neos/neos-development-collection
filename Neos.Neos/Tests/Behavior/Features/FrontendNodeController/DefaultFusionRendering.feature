@flowEntities
Feature: Test the default Fusion rendering for a request
  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:ContentCollection': {}
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
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Test.ContentType':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        text:
          type: string
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
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues                        | tetheredDescendantNodeAggregateIds | nodeName |
      | a               | root                  | Neos.Neos:Site              | {"title": "Node a"}                          | {}                                 | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType | {"uriPathSegment": "a1", "title": "Node a1"} | {"main": "a-tetherton" }           |          |
      | a1a1            | a-tetherton           | Neos.Neos:Test.ContentType  | {"text": "my first text"}                    | {}                                 |          |
      | a1a2            | a-tetherton           | Neos.Neos:Test.ContentType  | {"text": "my second text"}                   | {}                                 |          |
    And A site exists for node name "a" and domain "http://localhost" and package "Vendor.Site"
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
    And the Fusion code for package "Vendor.Site" is:
    """fusion
    prototype(Neos.Neos:Test.DocumentType) < prototype(Neos.Neos:Page) {
      body {
        content = Neos.Fusion:Component {
          renderer = afx`
            {String.chr(10)}title: {node.properties.title}
            {String.chr(10)}children: <Neos.Neos:ContentCollection nodePath='main' />
            {String.chr(10)}
          `
        }
      }
    }
    prototype(Neos.Neos:Test.ContentType) < prototype(Neos.Neos:ContentComponent) {
      text = Neos.Neos:Editable {
        property = 'text'
      }

      renderer = afx`
        [{props.text}]
      `
    }
    """

    When I dispatch the following request "/a1"
    Then I expect the following response:
    """
    HTTP/1.1 200 OK
    Content-Type: text/html
    X-Flow-Powered: Flow/dev Neos/dev

    <!DOCTYPE html><html>
    <!--
    This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.
    Neos is based on Flow, a powerful PHP application framework licensed under the MIT license.

    More information and contribution opportunities at https://www.neos.io
    -->
    <head><meta charset="UTF-8" /><title>Node a1</title></head><body class>
    title: Node a1
    children: <div class="neos-contentcollection">[my first text][my second text]</div>
    </body></html>
    """
