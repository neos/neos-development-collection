@flowEntities
Feature: ReadNodePrivilege related features

  Background:
    Given The following additional policies are configured:
      """
      privilegeTargets:
        'Neos\Neos\Security\Authorization\Privilege\ReadNodePrivilege':
          'Neos.Neos:ReadSubtreeA':
            matcher: 'subtree_a'
      roles:
        'Neos.Neos:RoleWithPrivilegeToReadSubtree':
          privileges:
            -
              privilegeTarget: 'Neos.Neos:ReadSubtreeA'
              permission: GRANT
      """
    And using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.Neos:Document':
      properties:
        foo:
          type: string
      references:
        ref: []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the live workspace exists
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName       | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | a               | Neos.Neos:Document | root                  | a        | {"language":"mul"}        |
      | a1              | Neos.Neos:Document | a                     | a1       | {"language":"de"}         |
      | a1a             | Neos.Neos:Document | a1                    | a1a      | {"language":"de"}         |
      | a1a1            | Neos.Neos:Document | a1a                   | a1a1     | {"language":"de"}         |
      | a1a1a           | Neos.Neos:Document | a1a1                  | a1a1a    | {"language":"de"}         |
      | a1a1b           | Neos.Neos:Document | a1a1                  | a1a1b    | {"language":"de"}         |
      | a1a2            | Neos.Neos:Document | a1a                   | a1a2     | {"language":"de"}         |
      | a1b             | Neos.Neos:Document | a1                    | a1b      | {"language":"de"}         |
      | a2              | Neos.Neos:Document | a                     | a2       | {"language":"de"}         |
      | b               | Neos.Neos:Document | root                  | b        | {"language":"de"}         |
      | b1              | Neos.Neos:Document | b                     | b1       | {"language":"de"}         |
    And the following Neos users exist:
      | Username              | First name | Last name  | Roles                                                     |
      | admin                 | Armin      | Admin      | Neos.Neos:Administrator                                   |
      | restricted_editor     | Rich       | Restricted | Neos.Neos:RestrictedEditor                                |
      | editor                | Edward     | Editor     | Neos.Neos:Editor                                          |
      | editor_with_privilege | Pete       | Privileged | Neos.Neos:Editor,Neos.Neos:RoleWithPrivilegeToReadSubtree |
    And I am in workspace "live"
    And I am in dimension space point {"language":"de"}
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "subtree_a"          |
    When a personal workspace for user "editor" is created
    And content repository security is enabled

  Scenario Outline: Read tagged node as user without corresponding ReadNodePrivilege
    And I am authenticated as "<user>"
    Then I should not be able to read node "a1"

    Examples:
      | user              |
      | admin             |
      | restricted_editor |
      | editor            |

  Scenario Outline: Read tagged node as user with corresponding ReadNodePrivilege
    And I am authenticated as "<user>"
    Then I should be able to read node "a1"

    Examples:
      | user                  |
      | editor_with_privilege |
