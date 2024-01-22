@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Set properties

  As a user of the CR I want to modify node properties.

  Background:
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, gsw | gsw->de->mul    |
    And using the following node types:
    """yaml
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
        dayOfWeek:
          type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
          defaultValue: 'https://schema.org/Wednesday'
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
          type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress
          defaultValue:
            streetAddress: '28 31st of February Street'
            postalCode: '12345'
            addressLocality: 'City'
            addressCountry: 'Country'
        price:
          type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PriceSpecification
          defaultValue:
            price: 13.37
            priceCurrency: 'EUR'
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
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeName | parentNodeAggregateId  | nodeTypeName                            |
      | nody-mc-nodeface | document | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"mul"} |
      | targetOrigin    | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"mul"} |
      | targetOrigin    | {"language":"gsw"} |
    And the graph projection is fully up to date

  Scenario: Set node properties
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                                                                                                                                                                                                                                                                                         |
      | contentStreamId           | "cs-identifier"                                                                                                                                                                                                                                                                                                                                               |
      | nodeAggregateId           | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                            |
      | originDimensionSpacePoint | {"language": "de"}                                                                                                                                                                                                                                                                                                                                            |
      | propertyValues            | {"string":"My new string", "int":8472, "float":72.84, "bool":true, "array":{"givenName":"David", "familyName":"Nodenborough","age":84}, "dayOfWeek":"DayOfWeek:https://schema.org/Friday", "date":"Date:2021-03-13T17:33:17+00:00", "uri":"URI:https://www.neos.io", "postalAddress":"PostalAddress:anotherDummy", "price":"PriceSpecification:anotherDummy"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                                         |
      | string        | "My new string"                                               |
      | int           | 8472                                                          |
      | float         | 72.84                                                         |
      | bool          | true                                                          |
      | array         | {"givenName":"David", "familyName":"Nodenborough", "age": 84} |
      | dayOfWeek     | DayOfWeek:https://schema.org/Friday                           |
      | date          | Date:2021-03-13T17:33:17+00:00                                |
      | uri           | URI:https://www.neos.io                                       |
      | postalAddress | PostalAddress:anotherDummy                                    |
      | price         | PriceSpecification:anotherDummy                               |

  Scenario: Set node properties, partially
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | contentStreamId           | "cs-identifier"            |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {"language": "de"}         |
      | propertyValues            | {"string":"My new string"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                                      |
      | string        | "My new string"                                            |
      | int           | 42                                                         |
      | float         | 84.72                                                      |
      | bool          | false                                                      |
      | array         | {"givenName":"Nody", "familyName":"McNodeface", "age": 42} |
      | dayOfWeek     | DayOfWeek:https://schema.org/Wednesday                     |
      | date          | Date:2020-08-20T18:56:15+00:00                             |
      | uri           | URI:https://neos.io                                        |
      | postalAddress | PostalAddress:dummy                                        |
      | price         | PriceSpecification:dummy                                   |
