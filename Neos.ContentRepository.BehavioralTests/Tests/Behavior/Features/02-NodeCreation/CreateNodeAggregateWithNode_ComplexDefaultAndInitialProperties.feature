@fixtures
Feature: Create a node aggregate with complex default values

  As a user of the CR I want default properties of complex types to be un/serialized

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Node':
      properties:
        array:
          type: array
          defaultValue:
            givenName: 'Nody'
            familyName: 'McNodeface'
        now:
          type: DateTimeImmutable
          defaultValue: 'now'
        date:
          type: DateTimeImmutable
          defaultValue: '2020-08-20T18:56:15+00:00'
        uri:
          type: GuzzleHttp\Psr7\Uri
          defaultValue: 'https://neos.io'
        postalAddress:
          type: Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress
          defaultValue:
            streetAddress: '28 31st of February Street'
            postalCode: '12345'
            addressLocality: 'City'
            addressCountry: 'Country'
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "user-id"            |
    And I am in content stream "cs-identifier"
    And I am in dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Create a node aggregate with complex default values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                 |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"              |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                           |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | postalAddress | PostalAddress:dummy                             |
      | now           | Date:now                                        |
      | date          | Date:2020-08-20T18:56:15+00:00                  |
      | uri           | URI:https://neos.io                             |

  Scenario: Create a node aggregate with complex initial and default values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                                                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Node"                                                                                    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                                                                 |
      | initialPropertyValues         | {"postalAddress":"PostalAddress:anotherDummy", "date":"Date:2021-03-13T17:33:17+00:00", "uri":"URI:https://www.neos.io"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                           |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | postalAddress | PostalAddress:anotherDummy                      |
      | now           | Date:now                                        |
      | date          | Date:2021-03-13T17:33:17+00:00                  |
      | uri           | URI:https://www.neos.io                         |
