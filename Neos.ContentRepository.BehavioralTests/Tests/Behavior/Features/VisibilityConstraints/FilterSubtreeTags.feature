@contentrepository @adapters=DoctrineDBAL
Feature: Filter nodes based on their subtree tags

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AbstractPage':
      abstract: true
    'Neos.ContentRepository.Testing:Homepage':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
    'Neos.ContentRepository.Testing:Page':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:Page | a2                     | {}                    | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2a2b           | a2a2b    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2a2c           | a2a2c    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2a2d           | a2a2d    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2b             | a2b      | Neos.ContentRepository.Testing:Page        | a2                     | {}                    | {}                                       |
      | a2b1            | a2b1     | Neos.ContentRepository.Testing:Page        | a2b                    | {}                    | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "a2a2c"     |
      | workspaceName                | "live"                 |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
      | tag                          | "mytag"              |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "a2a2d"     |
      | workspaceName                | "live"                 |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
      | tag                          | "mysecondtag"              |
  Scenario: Exclude Subtree Tag
    When VisibilityConstraints are set to "exclude" "mytag"
    And I execute the findDescendantNodes query for entry node aggregate id "a2a2" I expect the nodes "a2a2a,a2a2b,a2a2d" to be returned
  Scenario: Include Subtree Tag
    When VisibilityConstraints are set to "include" "mytag"
    And I execute the findDescendantNodes query for entry node aggregate id "a2a2" I expect the nodes "a2a2c" to be returned
    When VisibilityConstraints are set to "include" "nonexistenttag"
    And I execute the findDescendantNodes query for entry node aggregate id "a2a2" I expect no nodes to be returned
    When VisibilityConstraints are set to "include" "mytag,mysecondtag"
    And I execute the findDescendantNodes query for entry node aggregate id "a2a2" I expect the nodes "a2a2c,a2a2d" to be returned
  Scenario: Include and exclude Subtree Tag
    When VisibilityConstraints are set to "exclude and include" "mysecondtag" "mytag"
    And I execute the findDescendantNodes query for entry node aggregate id "a2a2" I expect the nodes "a2a2c" to be returned
