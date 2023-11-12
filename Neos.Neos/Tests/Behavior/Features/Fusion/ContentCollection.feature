@flowEntities
Feature: Tests for the "Neos.Neos:ContentCollection" Fusion prototype

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
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | contentStreamId | "cs-identifier"   |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName   |
      | a               | root                  | Neos.Neos:Site |
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
    And the Fusion context node is "a"
    And the Fusion context request URI is "http://localhost"

  Scenario: missing Neos.Neos.ContentCollection node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection
    """
    Then I expect the following Fusion rendering error:
    """
    No content collection of type Neos.Neos:ContentCollection could be found in the current node (/[root]) or at the path "to-be-set-by-user". You might want to adjust your node type configuration and create the missing child node through the "flow structureadjustments:fix --node-type Neos.Neos:Site" command.
    """

  Scenario: invalid nodePath
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection {
      nodePath = 'invalid'
    }
    """
    Then I expect the following Fusion rendering error:
    """
    No content collection of type Neos.Neos:ContentCollection could be found in the current node (/[root]) or at the path "invalid". You might want to adjust your node type configuration and create the missing child node through the "flow structureadjustments:fix --node-type Neos.Neos:Site" command.
    """

  Scenario: empty ContentCollection
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection {
      nodePath = 'main'
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """
    <div class="neos-contentcollection"></div>
    """

  Scenario:
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                | Value                         |
      | contentStreamId                    | "cs-identifier"               |
      | nodeAggregateId                    | "a1"                          |
      | nodeTypeName                       | "Neos.Neos:Test.DocumentType" |
      | parentNodeAggregateId              | "a"                           |
      | initialPropertyValues              | {}                            |
      | tetheredDescendantNodeAggregateIds | { "main": "a1-main"}          |
    And the graph projection is fully up to date
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName               |
      | content1        | a1-main               | Neos.Neos:Test.ContentType |
      | content2        | a1-main               | Neos.Neos:Test.ContentType |
    And the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.ContentType) < prototype(Neos.Fusion:Value) {
      value = ${node.nodeAggregateId.value + ' (' + node.nodeType.name.value + ') '}
    }

    test = Neos.Neos:ContentCollection {
      nodePath = 'main'
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """
    <div class="neos-contentcollection">content1 (Neos.Neos:Test.ContentType) content2 (Neos.Neos:Test.ContentType) </div>
    """
