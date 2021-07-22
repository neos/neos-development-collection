@fixtures
Feature: Constraint checks on SetNodeReferences

  As a user of the CR I expect invalid SetNodeReferences commands to be blocked

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
        nonReferenceProperty:
          type: string
    """
    And I am in content stream "cs-identifier" and dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | nodeAggregateIdentifier       | "source-nodandaise"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | nodeAggregateIdentifier       | "anthony-destinode"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | nodeAggregateIdentifier       | "berta-destinode"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date

  Scenario: Try to reference nodes in a non-existent content stream
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "i-do-not-exist"      |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet" with code 1521386692

  Scenario: Try to reference nodes in a non-existent node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "i-do-not-exist"      |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist" with code 1541678486

  Scenario: Try to reference a non-existent node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value               |
      | sourceNodeAggregateIdentifier       | "source-nodandaise" |
      | destinationNodeAggregateIdentifiers | ["i-do-not-exist"]  |
      | referenceName                       | "referenceProperty" |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist" with code 1541678486

  Scenario: Try to reference nodes in an undefined property:
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "i-do-not-exist"      |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1618670106

  Scenario: Try to reference nodes in a property that is not of type reference(s):
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                  |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"    |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"]  |
      | referenceName                       | "nonReferenceProperty" |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1618670106

  # @todo: Introduce reference node type constraints

  Scenario: Try to reference nodes in an origin dimension space point that does not exist
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                      |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"        |
      | sourceOriginDimensionSpacePoint     | {"undeclared":"undefined"} |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"]      |
      | referenceName                       | "referenceProperty"        |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound" with code 1520260137

  Scenario: Try to reference nodes in an origin dimension space point the source node aggregate does not occupy
    Given I have the following content dimensions:
      | Identifier | Default | Values       | Generalizations |
      | language   | mul     | mul, de, gsw | gsw->de->mul    |
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language":"de"}     |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
      | referenceName                       | "referenceProperty"   |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied" with code 1552595396
