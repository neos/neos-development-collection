@flowEntities
Feature: Tests for the "Neos.Neos:ContentCase" Fusion prototype

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
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                  |
      | a               | root                  | Neos.Neos:Site                |
      | a1              | a                     | Neos.Neos:Test.DocumentType2  |
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
    And the Fusion context node is "a1"
    And the Fusion context request URI is "http://localhost"

  Scenario: ContentCase without corresponding implementation
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCase
    """
    Then I expect the following Fusion rendering error:
    """
    The Fusion object "Neos.Neos:Test.DocumentType2" cannot be rendered:
    Most likely you mistyped the prototype name or did not define
    the Fusion prototype with "prototype(Neos.Neos:Test.DocumentType2) < prototype(...)".
    Other possible reasons are a missing parent-prototype or
    a missing "@class" annotation for prototypes without parent.
    It is also possible your Fusion file is not read because
    of a missing "include:" statement.
    """

  Scenario: ContentCase with corresponding implementation
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DocumentType2) < prototype(Neos.Fusion:Value) {
      value = 'implementation for DocumentType2'
    }

    test = Neos.Neos:ContentCase
    """
    Then I expect the following Fusion rendering result:
    """
    implementation for DocumentType2
    """
