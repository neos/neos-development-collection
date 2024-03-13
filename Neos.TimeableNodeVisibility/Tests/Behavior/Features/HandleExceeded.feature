@contentrepository
Feature: Simple handling of nodes with exceeded enableAfter and disableAfter datetime properties

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
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date


  # <===========================|now|===========================>
  #  -----|Enable|++++++++++++++|+++|+++++++++++++|Disable|-----
  Scenario: A enabled node with enableAfter in past and disableAfter in future must stay enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  -----|Enable|++++++++++++++|---|+++++++++++++|Disable|-----
  Scenario: A disabled node with enableAfter in past and disableAfter in future must be enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  +++++|Disable|-------------|+++|--------------|Enable|+++++
  Scenario: A enabled node with enableAfter in future and disableAfter past in must be disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"},"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  +++++|Disable|-------------|---|--------------|Enable|+++++
  Scenario: A disabled node with enableAfter in future and disableAfter past in must stay disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"},"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  ---|Enable|+++|Disable|----|+++|---------------------------
  Scenario: A enabled node with enableAfter and disableAfter in past, but enableAfter before disableAfter must be disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-9 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  ---|Enable|+++|Disable|----|---|---------------------------
  Scenario: A disabled node with enableAfter and disableAfter in past, but enableAfter before disableAfter must stay disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-9 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  +++|Disable|---|Enable|++++|+++|+++++++++++++++++++++++++++
  Scenario: A enabled node with enableAfter and disableAfter in past, but disableAfter before enableAfter must stay enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  +++|Disable|---|Enable|++++|---|+++++++++++++++++++++++++++
  Scenario: A disabled node with enableAfter and disableAfter in past, but disableAfter before enableAfter must be enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  +++++|Disable|-------------|+++|---------------------------
  Scenario: A enabled node with disableAfter past in must be disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  +++++|Disable|-------------|---|---------------------------
  Scenario: A enabled node with disableAfter past in must stay disabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  -----|Enable|++++++++++++++|+++|+++++++++++++++++++++++++++
  Scenario: A enabled node with enableAfter past in must stay enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  -----|Enable|++++++++++++++|---|+++++++++++++++++++++++++++
  Scenario: A disabled node with enableAfter past in must be enabled
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                            |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                               |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "-10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    Then I expect this node to be enabled
    And I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  ---------------------------|---|---|Enable|+++++|Disable|--
  Scenario: A disabled node with enableAfter and disableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"


    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  ---------------------------|+++|---|Enable|+++++|Disable|--
  Scenario: A enabled node with enableAfter and disableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  +++++++++++++++++++++++++++|---|+++|Disable|-----|Enable|++
  Scenario: A disabled node with disableAfter and enableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  +++++++++++++++++++++++++++|+++|+++|Disable|-----|Enable|++
  Scenario: A enabled node with disableAfter and enableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}, "disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  ---------------------------|---|---|Enable|+++++++++++++++
  Scenario: A disabled node with enableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"


    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  ---------------------------|+++|---|Enable|+++++++++++++++
  Scenario: A enabled node with enableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"enableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+9 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"


  # <===========================|now|===========================>
  #  +++++++++++++++++++++++++++|---|+++|Disable|---------------
  Scenario: A disabled node with disableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "duke-of-contentshire" |
      | nodeVariantSelectionStrategy | "allVariants"          |
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    Then I expect this node to be disabled
    And I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"

  # <===========================|now|===========================>
  #  +++++++++++++++++++++++++++|+++|+++|Disable|---------------
  Scenario: A enabled node with disableAfter and enableAfter in future must not be changed
    When I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | parentNodeAggregateId  | nodeTypeName          | initialPropertyValues                                                                                                                                                             |
      | shernode-homes       | lady-eleonode-rootford | Some.Package:Homepage | {}                                                                                                                                                                                |
      | duke-of-contentshire | shernode-homes         | Some.Package:Content  | {"disableAfterDateTime": {"__type": "DateTimeImmutable", "value": "+10 days"}} |
    Then I expect node aggregate identifier "duke-of-contentshire" to lead to node cs-identifier;duke-of-contentshire;{}
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"

    Then I handle exceeded node dates
    And I expect this node to be enabled
    And I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"
