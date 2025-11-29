@flowEntities
Feature: Tests for the EEL helpers interacting with the CR

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
        hiddenInMenu:
          type: bool
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Content':
      properties:
        title:
          type: string
    'Neos.Neos:ContentCollection':
      label: "${Neos.Node.labelForNode(node)}"
    'Neos.Neos:Test.DocumentType1':
      ui:
        label: "My Document 1"
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2a':
      superTypes:
        'Neos.Neos:Test.DocumentType2': true
    'Neos.Neos:Test.Content':
      superTypes:
        'Neos.Neos:Content': true
    'Neos.Neos:Test.Columns':
      superTypes:
        'Neos.Neos:Content': true
      childNodes:
        column0:
          label: 'Left Column'
          type: 'Neos.Neos:ContentCollection'
        column1:
          label: 'Right Column'
          type: 'Neos.Neos:ContentCollection'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

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
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                  | initialPropertyValues                                                | nodeName |
      | a               | root                  | Neos.Neos:Site                | {"title": "Node a"}                                                  | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1", "title": "Node a1"}                         | a1       |
      | a1a             | a1                    | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a", "title": "Node a1a"}                       | a1a      |
      | a1a1            | a1a                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a1", "title": "Node a1a1"}                     | a1a1     |
      | a1a2            | a1a                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1a2", "title": "Node a1a2"}                     | a1a2     |
      | a1a3            | a1a                   | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a3", "title": "Node a1a3"}                     | a1a3     |
      | a1a4            | a1a                   | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a4", "title": "Node a1a4"}                     | a1a4     |
      | a1a5            | a1a                   | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1a5", "title": "Node a1a5"}                     | a1a5     |
      | a1a6            | a1a                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1a6", "title": "Node a1a6"}                     | a1a6     |
      | a1a7            | a1a                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1a7", "title": "Node a1a7"}                     | a1a7     |
      | a1b             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b", "title": "Node a1b"}                       | a1b      |
      | a1b1            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1", "title": "Node a1b1"}                     | a1b1     |
      | a1b1a           | a1b1                  | Neos.Neos:Test.DocumentType2a | {"uriPathSegment": "a1b1a", "title": "Node a1b1a"}                   | a1b1a    |
      | a1b1b           | a1b1                  | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b1b", "title": "Node a1b1b"}                   | a1b1b    |
      | a1b2            | a1b                   | Neos.Neos:Test.DocumentType2  | {"uriPathSegment": "a1b2", "title": "Node a1b2"}                     | a1b2     |
      | a1b3            | a1b                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1b3", "title": "Node a1b3"}                     | a1b3     |
      | a1c             | a1                    | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c", "title": "Node a1c", "hiddenInMenu": true} | a1c      |
      | a1c1            | a1c                   | Neos.Neos:Test.DocumentType1  | {"uriPathSegment": "a1c1", "title": "Node a1c1"}                     | a1c1     |
      | a1d             | a1                    | Neos.Neos:Test.Columns        | {}                                                                   | a1d      |
      | b               | root                  | Neos.Neos:Site                | {"title": "Node b"}                                                  | b        |

    And A site exists for node name "a"
    And A site exists for node name "b"
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
    And I have the following Fusion setup:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    """

  Scenario: Neos.Site.findBySiteNode()
    When the Fusion context node is "a"
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      siteEntity_nodeName = ${String.toString(Neos.Site.findBySiteNode(node).nodeName)}
      siteEntity_title = ${Neos.Site.findBySiteNode(node).name}
      siteEntity_packageKey = ${Neos.Site.findBySiteNode(node).siteResourcesPackageKey}

      foreignSiteEntity = ${String.toString(Neos.Site.findBySiteNode(q(node).find('#b').get(0)).nodeName)}

      notASiteNode = ${Neos.Site.findBySiteNode(q(node).children().get(0))}
      @process.render = ${Json.stringify(value, ['JSON_PRETTY_PRINT'])}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {
        "siteEntity_nodeName": "a",
        "siteEntity_title": "Untitled Site",
        "siteEntity_packageKey": "Neos.Neos",
        "foreignSiteEntity": "b",
        "notASiteNode": null
    }
    """

  Scenario: Neos.Node.nodeType()
    When the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      nodeType_name = ${Neos.Node.nodeType(node).name}
      nodeType_label = ${Neos.Node.nodeType(node).label}
      nodeTypeName = ${node.nodeTypeName}
      @process.render = ${Json.stringify(value, ['JSON_PRETTY_PRINT'])}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {
        "nodeType_name": "Neos.Neos:Test.DocumentType1",
        "nodeType_label": "My Document 1",
        "nodeTypeName": "Neos.Neos:Test.DocumentType1"
    }
    """
    When I change the node types in content repository "default" to:
    """yaml
    """
    When I execute the following Fusion code:
    """fusion
    test = Neos.Fusion:DataStructure {
      nodeType = ${Neos.Node.nodeType(node)}
      nodeType_name = ${Neos.Node.nodeType(node).name}
      nodeType_label = ${Neos.Node.nodeType(node).label}
      nodeTypeName = ${node.nodeTypeName}
      @process.render = ${Json.stringify(value, ['JSON_PRETTY_PRINT'])}
    }
    """
    Then I expect the following Fusion rendering result:
    """
    {
        "nodeType": null,
        "nodeType_name": null,
        "nodeType_label": null,
        "nodeTypeName": "Neos.Neos:Test.DocumentType1"
    }
    """
