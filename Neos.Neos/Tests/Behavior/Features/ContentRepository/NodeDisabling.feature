@contentrepository @adapters=DoctrineDBAL
Feature: Special invariant checks for neos node disabling

  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': {}
    'Neos.ContentRepository.Testing:Document': {}
    'Neos.ContentRepository.Testing:DocumentWithTethered':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                                        | parentNodeAggregateId | nodeName | originDimensionSpacePoint | tetheredDescendantNodeAggregateIds |
      | a               | Neos.ContentRepository.Testing:DocumentWithTethered | root                  | a        | {"language":"mul"}        | {"tethered": "nodewyn-tetherton"}  |
      | a1              | Neos.ContentRepository.Testing:Document             | a                     | a1       | {"language":"de"}         |                                    |
      | a1a             | Neos.ContentRepository.Testing:Document             | a1                    | a1a      | {"language":"de"}         |                                    |
    Given I am in dimension space point {"language":"de"}

  Scenario: Disabling a tethered node aggregate is not allowed
    When the command TagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value               |
      | nodeAggregateId              | "nodewyn-tetherton" |
      | nodeVariantSelectionStrategy | "allVariants"       |
      | tag                          | "disabled"          |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"
