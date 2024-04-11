@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Recreate a node variant

  As a user of the CR I want to be able to recreate a variant after I deleted and published it
  See https://github.com/neos/neos-development-collection/issues/4583

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations |
      | language   | en, de, fr, gsw | gsw->de->en     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheredDocument':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-document:
          type: 'Neos.ContentRepository.Testing:TetheredDocument'
    'Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren': []
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

    And I am in the active content stream of workspace "live" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | originDimensionSpacePoint | nodeName            | parentNodeAggregateId  | nodeTypeName                                                   | tetheredDescendantNodeAggregateIds                                                       |
      | sir-david-nodenborough | {"language":"en"}         | document            | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodimus-prime", "tethered-document/tethered": "nodimus-mediocre"} |
      | nody-mc-nodeface       | {"language":"en"}         | grandchild-document | nodimus-prime          | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                       |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user-ws"    |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |

  Scenario: Create specialization variant of node, publish, delete it and recreate it
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "user-ws"                |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"en"}        |
      | targetOrigin    | {"language":"de"}        |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"en"}  |
      | targetOrigin    | {"language":"de"}  |
    And the command PublishWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-ws"        |
      | newContentStreamId | "new-user-cs-id" |

    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-ws"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language":"de"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"     |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "user-ws"                |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"en"}        |
      | targetOrigin    | {"language":"de"}        |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"en"}  |
      | targetOrigin    | {"language":"de"}  |

    When I am in the active content stream of workspace "user-ws" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node new-user-cs-id;sir-david-nodenborough;{"language": "de"}
    Then I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node new-user-cs-id;nodimus-prime;{"language": "de"}
    Then I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node new-user-cs-id;nodimus-mediocre;{"language": "de"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document/tethered-document/grandchild-document" to lead to node new-user-cs-id;nody-mc-nodeface;{"language": "de"}

