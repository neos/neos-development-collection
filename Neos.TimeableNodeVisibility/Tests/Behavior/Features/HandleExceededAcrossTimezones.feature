Feature: Handling of exceeded enableAfter and disableAfter datetime properties stored with different UTC offsets

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Site': {}
    'Neos.TimeableNodeVisibility:Timeable':
      properties:
        'disableAfterDateTime':
          type: DateTime
        'enableAfterDateTime':
          type: DateTime
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
        'Neos.TimeableNodeVisibility:Timeable': true
    'Some.Package:Content':
      superTypes:
        'Neos.TimeableNodeVisibility:Timeable': true

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

  Scenario Outline: An enabled node with disableAfter in the past stored in timezone "<zone>" must be disabled
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                    |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                       |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-50 minutes <zone>"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Examples:
      | zone                |
      | Pacific/Pago_Pago   |
      | America/Los_Angeles |
      | America/New_York    |
      | America/Sao_Paulo   |
      | UTC                 |
      | Europe/Berlin       |
      | Europe/Moscow       |
      | Asia/Kolkata        |
      | Asia/Tokyo          |
      | Pacific/Kiritimati  |

  Scenario Outline: A disabled node with enableAfter in the past stored in timezone "<zone>" must be enabled
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                   |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                      |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-50 minutes <zone>"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"

    Examples:
      | zone                |
      | Pacific/Pago_Pago   |
      | America/Los_Angeles |
      | America/New_York    |
      | America/Sao_Paulo   |
      | UTC                 |
      | Europe/Berlin       |
      | Europe/Moscow       |
      | Asia/Kolkata        |
      | Asia/Tokyo          |
      | Pacific/Kiritimati  |

  Scenario Outline: An enabled node with disableAfter in the future stored in timezone "<zone>" must not be changed
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                    |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                       |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+50 minutes <zone>"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Examples:
      | zone                |
      | Pacific/Pago_Pago   |
      | America/Los_Angeles |
      | America/New_York    |
      | America/Sao_Paulo   |
      | UTC                 |
      | Europe/Berlin       |
      | Europe/Moscow       |
      | Asia/Kolkata        |
      | Asia/Tokyo          |
      | Pacific/Kiritimati  |

  Scenario Outline: A disabled node with enableAfter in the future stored in timezone "<zone>" must not be changed
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                   |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                      |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+50 minutes <zone>"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Examples:
      | zone                |
      | Pacific/Pago_Pago   |
      | America/Los_Angeles |
      | America/New_York    |
      | America/Sao_Paulo   |
      | UTC                 |
      | Europe/Berlin       |
      | Europe/Moscow       |
      | Asia/Kolkata        |
      | Asia/Tokyo          |
      | Pacific/Kiritimati  |
