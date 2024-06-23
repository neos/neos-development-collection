@flowEntities
Feature: Tests for the ContentCacheFlusher and cache flushing when applied in user workspaces

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:ContentCollection':
      constraints:
        nodeTypes:
          'Neos.Neos:Document': false
          '*': true
    'Neos.Neos:Content':
      constraints:
        nodeTypes:
          '*': false
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentTypeWithMainCollection':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Test.TextNode':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "editor"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                       |
      | workspaceName      | "user-editor"               |
      | baseWorkspaceName  | "live"                      |
      | newContentStreamId | "user-editor-cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | parentNodeAggregateId             | nodeTypeName                                  | initialPropertyValues                                                                     | nodeName                    | tetheredDescendantNodeAggregateIds            |
      | site                        | root                              | Neos.Neos:Site                                | {}                                                                                        | site                        | {}                                            |
      | test-document-with-contents | site                              | Neos.Neos:Test.DocumentTypeWithMainCollection | {"uriPathSegment": "test-document-with-contents", "title": "Test document with contents"} | test-document-with-contents | {"main": "test-document-with-contents--main"} |
      | text-node-start             | test-document-with-contents--main | Neos.Neos:Test.TextNode                       | {"text": "Text Node at the start of the document"}                                        | text-node-start             | {}                                            |
      | text-node-end               | test-document-with-contents--main | Neos.Neos:Test.TextNode                       | {"text": "Text Node at the end of the document"}                                          | text-node-end               | {}                                            |
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value         |
      | workspaceName | "user-editor" |
    And A site exists for node name "site" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And the Fusion context node is "site"
    And the Fusion context request URI is "http://localhost"
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.TextNode) < prototype(Neos.Neos:ContentComponent) {
      renderer = ${"[" + q(node).property("text") + "]"}
    }
    """

  Scenario: ContentCache gets flushed when a node that was just created gets discarded
    Given I have Fusion content cache enabled
    And I am in workspace "user-editor" and dimension space point {}
    And the Fusion context node is "test-document-with-contents"
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:ContentCollection {
      nodePath = "main"
    }
    """
    Then I expect the following Fusion rendering result:
    """
    <div class="neos-contentcollection">[Text Node at the start of the document][Text Node at the end of the document]</div>
    """

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                               | Value                                               |
      | nodeAggregateId                   | "text-node-middle"                                  |
      | nodeTypeName                      | "Neos.Neos:Test.TextNode"                           |
      | parentNodeAggregateId             | "test-document-with-contents--main"                 |
      | initialPropertyValues             | {"text": "Text Node in the middle of the document"} |
      | succeedingSiblingNodeAggregateId  | "text-node-end"                                     |
      | nodeName                          | "text-node-middle"                                  |
    And I execute the following Fusion code:
    """fusion
    test = Neos.Neos:ContentCollection {
      nodePath = "main"
    }
    """
    Then I expect the following Fusion rendering result:
    """
    <div class="neos-contentcollection">[Text Node at the start of the document][Text Node in the middle of the document][Text Node at the end of the document]</div>
    """

    When the command DiscardWorkspace is executed with payload:
      | Key           | Value         |
      | workspaceName | "user-editor" |
    # FIXME we have to reevaluated the step as we cache the $currentContentStreamId and it will be outdated after the discard
    # see https://github.com/neos/neos-development-collection/pull/5162
    And I am in workspace "user-editor" and dimension space point {}
    Then I expect node aggregate identifier "text-node-middle" to lead to no node

    When I execute the following Fusion code:
    """fusion
    test = Neos.Neos:ContentCollection {
      nodePath = "main"
    }
    """
    Then I expect the following Fusion rendering result:
    """
    <div class="neos-contentcollection">[Text Node at the start of the document][Text Node at the end of the document]</div>
    """
