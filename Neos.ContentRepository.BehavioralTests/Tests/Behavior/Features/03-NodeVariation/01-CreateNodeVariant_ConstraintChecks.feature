@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node variant

  As a user of the CR I want to create a copy of a node within an aggregate to another dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | market     | DE, CH  | CH->DE          |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:RestrictiveDocument':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': false
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:Document': false
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"market":"DE", "language":"gsw"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                             |
      | nodeAggregateId                    | "lady-eleonode-rootford"          |
      | nodeTypeName                       | "Neos.ContentRepository:Root"     |
      | tetheredDescendantNodeAggregateIds | {"tethered": "nodimer-tetherton"} |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                            | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | document | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | {"tethered": "nodewyn-tetherton"}  |
    # We have to add yet another node since we need test cases with a partially covering parent node
    # Node /document/child
      | nody-mc-nodeface       | child    | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | {}                                 |
    And I am in workspace "live" and dimension space point {"market":"DE", "language":"de"}
    # We have to add yet another node that could be varied but not to a different parent
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | nodeName       | parentNodeAggregateId  | nodeTypeName                            |
      | polyglot-mc-nodeface | polyglot-child | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |
    # ...and we have to add yet another node for node type constraint checks
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | nodeName       | parentNodeAggregateId  | nodeTypeName                            | tetheredDescendantNodeAggregateIds |
      | the-governode        | governode | lady-eleonode-rootford | Neos.ContentRepository.Testing:RestrictiveDocument | {"tethered": "another-tetherton"} |

  Scenario: Try to create a variant in a workspace that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | workspaceName   | "i-do-not-exist-yet"              |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "WorkspaceDoesNotExist"

  Scenario: Try to create a variant in a workspace that does not exist
    When the event ContentStreamWasClosed was published with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    And the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  Scenario: Try to create a variant in a node aggregate that currently does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "i-currently-do-not-exist"        |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant of a root node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "lady-eleonode-rootford"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to create a variant in a tethered node aggregate which is not a child of a root node aggregate
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "nodewyn-tetherton"               |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to create a variant from a source dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                            |
      | nodeAggregateId | "sir-david-nodenborough"         |
      | sourceOrigin    | {"undeclared":"undefined"}       |
      | targetOrigin    | {"market":"DE", "language":"de"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant from a source dimension space point that the node aggregate does not occupy
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a variant to a target dimension space point that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"CH", "language":"gsw"} |
      | targetOrigin    | {"undeclared":"undefined"}        |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a variant to a target dimension space point that the node aggregate already occupies
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "sir-david-nodenborough"          |
      | sourceOrigin    | {"market":"DE", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a variant to a target dimension space point that neither the node aggregate nor its parent in the source dimension point cover
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key             | Value                             |
      | nodeAggregateId | "nody-mc-nodeface"                |
      | sourceOrigin    | {"market":"DE", "language":"gsw"} |
      | targetOrigin    | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Creating variants of tethered root children is allowed
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                             |
      | nodeAggregateId | "nodimer-tetherton"               |
      | sourceOrigin    | {"market":"DE", "language":"de"}  |
      | targetOrigin    | {"market":"DE", "language":"gsw"} |
    # no exceptions

  Scenario: Try to create a variant as a child of a different parent aggregate that does not exist
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                   | Value                             |
      | nodeAggregateId       | "polyglot-mc-nodeface"            |
      | sourceOrigin          | {"market":"DE", "language":"de"}  |
      | targetOrigin          | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId | "i-do-not-exist"                  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant as a sibling of a non-existing succeeding sibling
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                              | Value                             |
      | nodeAggregateId                  | "polyglot-mc-nodeface"            |
      | sourceOrigin                     | {"market":"DE", "language":"de"}  |
      | targetOrigin                     | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId            | "nody-mc-nodeface"                |
      | succeedingSiblingNodeAggregateId | "i-do-not-exist"                  |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to create a variant before a sibling which is not a child of the new parent
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                              | Value                             |
      | nodeAggregateId                  | "polyglot-mc-nodeface"            |
      | sourceOrigin                     | {"market":"DE", "language":"de"}  |
      | targetOrigin                     | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId            | "nody-mc-nodeface"                |
      | succeedingSiblingNodeAggregateId | "sir-david-nodenborough"          |
    Then the last command should have thrown an exception of type "NodeAggregateIsNoChild"

  Scenario: Try to create a variant before a sibling which is none (no new parent case)
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                              | Value                             |
      | nodeAggregateId                  | "polyglot-mc-nodeface"            |
      | sourceOrigin                     | {"market":"DE", "language":"de"}  |
      | targetOrigin                     | {"market":"DE", "language":"gsw"} |
      | succeedingSiblingNodeAggregateId | "nody-mc-nodeface"                |
    Then the last command should have thrown an exception of type "NodeAggregateIsNoSibling"

  Scenario: Try to create a variant as a child of a different parent aggregate that does not cover the requested DSP
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                   | Value                            |
      | nodeAggregateId       | "polyglot-mc-nodeface"           |
      | sourceOrigin          | {"market":"DE", "language":"de"} |
      | targetOrigin          | {"market":"CH", "language":"de"} |
      | parentNodeAggregateId | "sir-david-nodenborough"         |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to create a variant of a node having a name that is already taken by one of the variant's siblings
    Given I am in workspace "live" and dimension space point {"market":"DE", "language":"gsw"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName       | parentNodeAggregateId | nodeTypeName                            |
      | evil-occupant   | polyglot-child | nody-mc-nodeface      | Neos.ContentRepository.Testing:Document |

    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                   | Value                             |
      | nodeAggregateId       | "polyglot-mc-nodeface"            |
      | sourceOrigin          | {"market":"DE", "language":"de"}  |
      | targetOrigin          | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId | "nody-mc-nodeface"                |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to vary a node as a child of another parent whose node type does not allow child nodes of the variant's type
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                   | Value                             |
      | nodeAggregateId       | "polyglot-mc-nodeface"            |
      | sourceOrigin          | {"market":"DE", "language":"de"}  |
      | targetOrigin          | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId | "the-governode"                |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to vary a node as a child of another parent whose parent's node type does not allow grand child nodes of the variant's type
    When the command CreateNodeVariant is executed with payload and exceptions are caught:
      | Key                   | Value                             |
      | nodeAggregateId       | "polyglot-mc-nodeface"            |
      | sourceOrigin          | {"market":"DE", "language":"de"}  |
      | targetOrigin          | {"market":"DE", "language":"gsw"} |
      | parentNodeAggregateId | "another-tetherton"                |
    Then the last command should have thrown an exception of type "NodeConstraintException"
