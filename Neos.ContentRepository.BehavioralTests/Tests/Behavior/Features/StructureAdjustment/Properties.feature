@contentrepository @adapters=DoctrineDBAL
Feature: Properties

  As a user of the CR I want to be able to detect and handle properties:

  - set new default values
  - remove obsolete properties

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        myProp:
          type: string
          defaultValue: "Foo"
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # Node /document
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                       | Value                                     |
      | contentStreamId           | "cs-identifier"                           |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key    | Value |
      | myProp | "Foo" |

  Scenario: The property is removed
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type              | nodeAggregateId        |
      | OBSOLETE_PROPERTY | sir-david-nodenborough |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no properties

  Scenario: a new property default value is set
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        myProp:
          type: string
          defaultValue: "Foo"
        otherProp:
          type: string
          defaultValue: "foo"
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateId        |
      | MISSING_DEFAULT_VALUE | sir-david-nodenborough |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key       | Value |
      | myProp    | "Foo" |
      | otherProp | "foo" |

  Scenario: a new property default value is not set if the value already contains the empty string
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        myProp:
          type: string
          defaultValue: "Foo"
        otherProp:
          type: string
          defaultValue: "foo"
    """
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | contentStreamId           | "cs-identifier"          |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {}                       |
      | propertyValues            | {"otherProp": ""}        |
    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

  Scenario: a broken property (which cannot be deserialized) is detected and removed
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        myProp:
          # we need to disable the default value; as otherwise, the "MISSING_DEFAULT_VALUE" check will trigger after the property has been removed.
          type: string
    """

    And the Event "NodePropertiesWereSet" was published to stream "ContentStream:cs-identifier" with payload:
      | Key                          | Value                                                                       |
      | contentStreamId              | "cs-identifier"                                                             |
      | nodeAggregateId              | "sir-david-nodenborough"                                                    |
      | originDimensionSpacePoint    | {}                                                                          |
      | affectedDimensionSpacePoints | [{}]                                                                        |
      | propertyValues               | {"myProp": {"value": "original value", "type": "My\\Non\\Existing\\Class"}} |
    And the graph projection is fully up to date
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                        | nodeAggregateId        |
      | NON_DESERIALIZABLE_PROPERTY | sir-david-nodenborough |
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no properties
