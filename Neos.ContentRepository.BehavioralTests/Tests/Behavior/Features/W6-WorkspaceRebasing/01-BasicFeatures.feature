@contentrepository @adapters=DoctrineDBAL
Feature: Rebasing with no conflict

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content': {}
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        child1:
          type: 'Neos.ContentRepository.Testing:Content'
        child2:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    When I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                               |
      | workspaceName               | "live"                                              |
      | nodeAggregateId             | "sir-david-nodenborough"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                            |

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

    Then workspaces live,user-test have status UP_TO_DATE

  Scenario: Rebase is a no-op if there are no changes
    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
    Then I expect the content stream "user-cs-rebased" to not exist

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier;sir-david-nodenborough;{}

    # only if the force flag is used we enforce a fork:
    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
      | rebaseErrorHandlingStrategy | "force"               |
    Then I expect the content stream "user-cs-identifier" to not exist

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-rebased;sir-david-nodenborough;{}

  Scenario: Rebase only the base contains changes
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    Then workspaces user-test has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
    Then I expect the content stream "user-cs-identifier" to not exist
    Then workspaces live,user-test have status UP_TO_DATE

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-rebased;sir-nodeward-nodington-iii;{}


  Scenario: Rebase workspace and base contains changes
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "user-test"                                             |
      | nodeAggregateId             | "nordisch-nodel"                                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    Then workspaces user-test has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
    Then I expect the content stream "user-cs-identifier" to not exist

    Then workspaces live,user-test have status UP_TO_DATE

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to no node

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-rebased;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to node user-cs-rebased;nordisch-nodel;{}
