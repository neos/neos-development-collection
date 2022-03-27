@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Set properties

  As a user of the CR I want to modify node properties.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values       | Generalizations |
      | language   | mul     | mul, de, gsw | gsw->de->mul    |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        string:
          type: string
          defaultValue: 'My string'
        int:
          type: int
          defaultValue: 42
        float:
          type: float
          defaultValue: 84.72
        bool:
          type: bool
          defaultValue: false
        array:
          type: array
          defaultValue:
            givenName: 'Nody'
            familyName: 'McNodeface'
            age: 42
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
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeName | parentNodeAggregateIdentifier | nodeTypeName                            |
      | nody-mc-nodeface        | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"gsw"} |
    And the graph projection is fully up to date

  Scenario: Set node properties
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                                                                                                                                                                                           |
      | contentStreamIdentifier   | "cs-identifier"                                                                                                                                                                                                                                                 |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                                                                                                                                                                                                              |
      | originDimensionSpacePoint | {"language": "de"}                                                                                                                                                                                                                                              |
      | propertyValues            | {"string":"My new string", "int":8472, "float":72.84, "bool":true, "array":{"givenName":"David", "familyName":"Nodenborough","age":84}, "date":"Date:2021-03-13T17:33:17+00:00", "uri":"URI:https://www.neos.io", "postalAddress":"PostalAddress:anotherDummy"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                                         |
      | string        | "My new string"                                               |
      | int           | 8472                                                          |
      | float         | 72.84                                                         |
      | bool          | true                                                          |
      | array         | {"givenName":"David", "familyName":"Nodenborough", "age": 84} |
      | date          | Date:2021-03-13T17:33:17+00:00                                |
      | uri           | URI:https://www.neos.io                                       |
      | postalAddress | PostalAddress:anotherDummy                                    |
