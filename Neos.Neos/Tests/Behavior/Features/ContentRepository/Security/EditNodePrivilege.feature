@flowEntities
Feature: EditNodePrivilege related features

  Background:
    Given The following additional policies are configured:
      """
      privilegeTargets:
        'Neos\Neos\Security\Authorization\Privilege\EditNodePrivilege':
          'Neos.Neos:EditSubtreeA':
            matcher: 'subtree_a'
      roles:
        'Neos.Neos:RoleWithPrivilegeToEditSubtree':
          privileges:
            -
              privilegeTarget: 'Neos.Neos:EditSubtreeA'
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
      | editor_with_privilege | Pete       | Privileged | Neos.Neos:Editor,Neos.Neos:RoleWithPrivilegeToEditSubtree |
    And I am in workspace "live"
    And I am in dimension space point {"language":"de"}
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "subtree_a"          |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a1a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the role COLLABORATOR is assigned to workspace "live" for group "Neos.Neos:Editor"
    When a personal workspace for user "editor" is created
    And content repository security is enabled

  Scenario Outline: Handling all relevant EditNodePrivilege related commands with different users
    Given I am authenticated as "editor"
    Then the Neos user "editor" for node "<node aggregate id>" should have the permissions ""
    When the command <command> is executed with payload '<command payload>' and exceptions are caught
    Then the last command should have thrown an exception of type "AccessDenied" with code 1729086686

    When I am authenticated as "restricted_editor"
    Then the Neos user "restricted_editor" for node "<node aggregate id>" should have the permissions ""
    When the command <command> is executed with payload '<command payload>' and exceptions are caught
    Then the last command should have thrown an exception of type "AccessDenied" with code 1729086686

    When I am authenticated as "admin"
    Then the Neos user "admin" for node "<node aggregate id>" should have the permissions ""
    When the command <command> is executed with payload '<command payload>' and exceptions are caught
    Then the last command should have thrown an exception of type "AccessDenied" with code 1729086686

    When I am authenticated as "editor_with_privilege"
    Then the Neos user "editor_with_privilege" for node "<node aggregate id>" should have the permissions "edit,remove"
    And the command <command> is executed with payload '<command payload>'
    # command should not fail, no error here :)

    When I am in workspace "edward-editor"
    And the command <command> is executed with payload '<command payload>' and exceptions are caught
    Then the last command should have thrown an exception of type "AccessDenied" with code 1729086686

    Examples:
      | command                     | command payload                                                                                        | node aggregate id |
      | CreateNodeAggregateWithNode | {"nodeAggregateId":"a1b1","parentNodeAggregateId":"a1b","nodeTypeName":"Neos.Neos:Document"}           | a1b               |
      | CreateNodeVariant           | {"nodeAggregateId":"a1","sourceOrigin":{"language":"de"},"targetOrigin":{"language":"en"}}             | a1                |
      | DisableNodeAggregate        | {"nodeAggregateId":"a1","nodeVariantSelectionStrategy":"allVariants"}                                  | a1                |
      | EnableNodeAggregate         | {"nodeAggregateId":"a1a1a","nodeVariantSelectionStrategy":"allVariants"}                               | a1a1a             |
      | RemoveNodeAggregate         | {"nodeAggregateId":"a1","nodeVariantSelectionStrategy":"allVariants"}                                  | a1                |
      | TagSubtree                  | {"nodeAggregateId":"a1","tag":"some_tag","nodeVariantSelectionStrategy":"allVariants"}                 | a1                |
      | UntagSubtree                | {"nodeAggregateId":"a","tag":"subtree_a","nodeVariantSelectionStrategy":"allVariants"}                 | a                 |
      | MoveNodeAggregate           | {"nodeAggregateId":"a1","newParentNodeAggregateId":"b"}                                                | a1                |
      | SetNodeProperties           | {"nodeAggregateId":"a1","propertyValues":{"foo":"bar"}}                                                | a1                |
      | SetNodeReferences           | {"sourceNodeAggregateId":"a1","references":[{"referenceName": "ref", "references": [{"target":"b"}]}]} | a1                |
