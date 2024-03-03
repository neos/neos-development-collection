@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create a node aggregate with complex default values

  As a user of the CR I want default properties of complex types to be un/serialized

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        array:
          type: array
          defaultValue:
            givenName: 'Nody'
            familyName: 'McNodeface'
        dayOfWeek:
          type: Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek
          defaultValue: 'https://schema.org/Wednesday'
        now:
          type: DateTime
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

    'Neos.ContentRepository.Testing:FaultyDateNode':
      properties:
        date:
          type: DateTimeImmutable
          defaultValue: 'not a date'
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
    And I am in content stream "cs-identifier"
    And I am in dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Create a node aggregate with complex default values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                 |
      | nodeAggregateId       | "nody-mc-nodeface"                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph

    And I expect this node to have the following serialized properties:
      | Key           | Type                                                                   | Value                                                              |
      | array         | array                                                                  | {"givenName":"Nody","familyName":"McNodeface"}                     |
      | dayOfWeek     | Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek          | "https://schema.org/Wednesday"                                     |
      | postalAddress | Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress      | {"streetAddress":"28 31st of February Street","postalCode":12345,"addressLocality":"City","addressCountry":"Country"} |
      # DateTime must always be treated as immutable see DateTimeImmutable.
      # And the default value "now" must not be serialized as string "now" but as its actual value of the time of the command:
      | now           | DateTimeImmutable                                                      | NOT:"now"                                                          |
      | date          | DateTimeImmutable                                                      | "2020-08-20T18:56:15+00:00"                                        |
      | uri           | GuzzleHttp\Psr7\Uri                                                    | "https://neos.io"                                                  |
      # Defaults while deserializing value objects will be manifested at the time of the command: (valueAddedTaxIncluded was not explicitly declared above)
      | price         | Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PriceSpecification | {"price":13.37,"priceCurrency":"EUR","valueAddedTaxIncluded":true} |

    And I expect this node to have the following properties:
      | Key           | Value                                           |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | dayOfWeek     | DayOfWeek:https://schema.org/Wednesday          |
      | postalAddress | PostalAddress:dummy                             |
      | now           | Date:now                                        |
      | date          | Date:2020-08-20T18:56:15+00:00                  |
      | uri           | URI:https://neos.io                             |
      | price         | PriceSpecification:dummy                        |

  Scenario: Create a node aggregate with complex initial and default values
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                                                                                                                                                                                                 |
      | nodeAggregateId       | "nody-mc-nodeface"                                                                                                                                                                                                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node"                                                                                                                                                                                 |
      | parentNodeAggregateId | "lady-eleonode-rootford"                                                                                                                                                                                              |
      | initialPropertyValues | {"dayOfWeek":"DayOfWeek:https://schema.org/Friday","postalAddress":"PostalAddress:anotherDummy", "date":"Date:2021-03-13T17:33:17+00:00", "uri":"URI:https://www.neos.io", "price":"PriceSpecification:anotherDummy"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to have the following properties:
      | Key           | Value                                           |
      | dayOfWeek     | DayOfWeek:https://schema.org/Friday             |
      | array         | {"givenName":"Nody", "familyName":"McNodeface"} |
      | postalAddress | PostalAddress:anotherDummy                      |
      | now           | Date:now                                        |
      | date          | Date:2021-03-13T17:33:17+00:00                  |
      | uri           | URI:https://www.neos.io                         |
      | price         | PriceSpecification:anotherDummy                 |

  Scenario: Create a node aggregate with faulty date time defaultValue fails
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                   | Value                                           |
      | nodeAggregateId       | "nody-mc-nodeface"                              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:FaultyDateNode" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                        |
    And the last command should have thrown an exception of type "RuntimeException" with code 1708416598
