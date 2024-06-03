@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Constraint checks on SetNodeReferences

  As a user of the CR I expect invalid SetNodeReferences commands to be blocked

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, en | gsw->de, en     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:ReferencedNode': []

    'Neos.ContentRepository.Testing:NodeWithReferences':
      # legacy notation
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
        nonReferenceProperty:
          type: string
      references:
        constrainedReferenceCount:
          constraints:
            maxItems: 1
        constrainedReferenceProperty:
          constraints:
            maxItems: 1
            nodeTypes:
              'Neos.ContentRepository.Testing:ReferencedNode': false
        referencePropertyWithProperties:
          properties:
            text:
              type: string
            postalAddress:
              type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | nodeTypeName                                      | parentNodeAggregateId  |
      | source-nodandaise | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford |
      | anthony-destinode | Neos.ContentRepository.Testing:ReferencedNode     | lady-eleonode-rootford |
      | berta-destinode   | Neos.ContentRepository.Testing:ReferencedNode     | lady-eleonode-rootford |

  Scenario: Try to reference nodes in a workspace whose content stream is closed
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "source-nodandaise"              |
      | referenceName         | "referenceProperty"              |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  # checks for contentStreamId
  Scenario: Try to reference nodes in a non-existent content stream
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | workspaceName         | "i-do-not-exist"                 |
      | sourceNodeAggregateId | "source-nodandaise"              |
      | referenceName         | "referenceProperty"              |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "WorkspaceDoesNotExist" with code 1513924741

  # checks for sourceNodeAggregateId
  Scenario: Try to reference nodes in a non-existent node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "i-do-not-exist"                 |
      | referenceName         | "referenceProperty"              |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist" with code 1541678486

  Scenario: Try to reference nodes in a root node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "lady-eleonode-rootford"         |
      | referenceName         | "referenceProperty"              |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  # checks for sourceOriginDimensionSpacePoint
  Scenario: Try to reference nodes in an origin dimension space point that does not exist
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                             | Value                            |
      | sourceNodeAggregateId           | "source-nodandaise"              |
      | sourceOriginDimensionSpacePoint | {"undeclared":"undefined"}       |
      | referenceName                   | "referenceProperty"              |
      | references                      | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound" with code 1505929456

  Scenario: Try to reference nodes in an origin dimension space point the source node aggregate does not occupy
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                             | Value                            |
      | sourceNodeAggregateId           | "source-nodandaise"              |
      | sourceOriginDimensionSpacePoint | {"language":"en"}                |
      | referenceName                   | "referenceProperty"              |
      | references                      | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied" with code 1552595396

  # checks for destinationnodeAggregateIds
  Scenario: Try to reference a non-existent node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                         |
      | sourceNodeAggregateId | "source-nodandaise"           |
      | referenceName         | "referenceProperty"           |
      | references            | [{"target":"i-do-not-exist"}] |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist" with code 1541678486

  Scenario: Try to reference a root node aggregate
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                 |
      | sourceNodeAggregateId | "source-nodandaise"                   |
      | referenceName         | "referenceProperty"                   |
      | references            | [{"target":"lady-eleonode-rootford"}] |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to set references exceeding the maxItems count
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                                          |
      | sourceNodeAggregateId | "source-nodandaise"                                            |
      | referenceName         | "constrainedReferenceCount"                                    |
      | references            | [{"target":"anthony-destinode"}, {"target":"berta-destinode"}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1700150156

  Scenario: Try to set references exceeding the maxItems count for legacy property reference declaration
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                                          |
      | sourceNodeAggregateId | "source-nodandaise"                                            |
      | referenceName         | "referenceProperty"                                            |
      | references            | [{"target":"anthony-destinode"}, {"target":"berta-destinode"}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1700150156

  Scenario: Try to reference a node aggregate of a type not matching the constraints
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "source-nodandaise"              |
      | referenceName         | "constrainedReferenceProperty"   |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1648502149

  Scenario: Try to reference a node aggregate which does not cover the source origin
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                               |
      | nodeAggregateId           | "sir-david-nodenborough"                            |
      | nodeTypeName              | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | originDimensionSpacePoint | {"language":"en"}                                   |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                            |

    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                             | Value                                 |
      | sourceNodeAggregateId           | "source-nodandaise"                   |
      | sourceOriginDimensionSpacePoint | {"language": "de"}                    |
      | referenceName                   | "referenceProperty"                   |
      | references                      | [{"target":"sir-david-nodenborough"}] |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  # checks for referenceName
  Scenario: Try to reference nodes in an undefined property:
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "source-nodandaise"              |
      | referenceName         | "i-do-not-exist"                 |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1618670106

  Scenario: Try to reference nodes in a property that is not of type reference(s):
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | sourceNodeAggregateId | "source-nodandaise"              |
      | referenceName         | "nonReferenceProperty"           |
      | references            | [{"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1618670106

  Scenario: Try to reference a node aggregate using a property the reference does not declare
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                                                         |
      | nodeAggregateId       | "nody-mc-nodeface"                                                            |
      | sourceNodeAggregateId | "source-nodandaise"                                                           |
      | referenceName         | "referencePropertyWithProperties"                                             |
      | references            | [{"target":"anthony-destinode", "properties":{"i-do-not-exist": "whatever"}}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1658406662

  Scenario: Try to set a property with a value of a wrong type
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                                                                          |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                             |
      | sourceNodeAggregateId | "source-nodandaise"                                                                            |
      | referenceName         | "referencePropertyWithProperties"                                                              |
      | references            | [{"target":"anthony-destinode", "properties":{"postalAddress": "28 31st of February Street"}}] |
    Then the last command should have thrown an exception of type "ReferenceCannotBeSet" with code 1658406762

  Scenario: Node reference cannot hold multiple targets to the same node
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                   | Value                                                            |
      | sourceNodeAggregateId | "source-nodandaise"                                              |
      | referenceName         | "referencesProperty"                                             |
      | references            | [{"target":"anthony-destinode"}, {"target":"anthony-destinode"}] |
    Then the last command should have thrown an exception of type "InvalidArgumentException" with code 1700150910
