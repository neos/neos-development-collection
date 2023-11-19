@contentrepository @adapters=DoctrineDBAL
Feature: Tag subtree with dimensions

  As a user of the CR I want to tag a node and expect its descendants to also be tagged.

  These are the test cases with dimensions being involved

  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
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
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId         | nodeTypeName                            | parentNodeAggregateId  | nodeName            |
      | preceding-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | preceding-document  |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document            |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | succeeding-document |
      | nody-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document      |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                  |
      | sourceNodeAggregateId | "preceding-nodenborough"               |
      | referenceName         | "references"                           |
      | references            | [{"target": "sir-david-nodenborough"}] |
    # We need both a real and a virtual specialization to test the different selection strategies
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"mul"}       |
      | targetOrigin    | {"language":"ltz"}       |
    And the graph projection is fully up to date
    # Set the DSP to the "central" variant having variants of all kind
    And I am in dimension space point {"language":"de"}

  Scenario: Disable node aggregate with strategy allSpecializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "some_tag"               |

    Then I expect exactly 9 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 8 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                                    |
      | contentStreamId              | "cs-identifier"                                             |
      | nodeAggregateId              | "sir-david-nodenborough"                                    |
      | affectedDimensionSpacePoints | [{"language":"de"}, {"language":"ltz"}, {"language":"gsw"}] |
      | tag                          | "some_tag"                                                  |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 6 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    #And I expect this node aggregate to disable dimension space points [{"language":"de"}, {"language":"ltz"}, {"language":"gsw"}]
