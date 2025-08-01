# Log of important architecture decisions


## Single Queries in Favor of PHP business logic (Event Handlers / write side)

Eric Kloss, 1.8.2025

The event handlers are designed to perform their respective write actions
in a **single SQL query** if possible.

Advantages:

 - !!! removes any dependency to the read side API from the event handlers
 - There is no need to query and keep interim results in PHP memory. F.e. in most cases, the "PHP world" does not need
   to know about **relation anchor points** as they are an internal ID.
   - also, event handlers never actually return anything (always void)
   - that also removes the "Active Record" pattern in the event handlers
      -> for me that was a massive cognitive overhead (I wondered why we need the domain objects to be in sync 
         with the DB when we never actually return anything a.k.a. it is "unused" after setting it)
   - Also, in other places but the event handlers, those domain classes were used as well.
     That kind of felt like "god components", which made it hard for me to "assign a function to a specific use-case".
 - PHP loops get replaced by JOIN queries, multi-inserts / updates: this way, we:
   - categorically exclude the possibility to nest loops, in loops, in loops, ... and do lots of SQL queries in
     a single event handling action
   - slow event handlers that need to be optimized will show up in the **slow query log** instead of "flooding" 
     the log with "fast but lot's" of queries
   - set operations, aggregations and filters can take advantage of **database indices**
 - the common "upsert" pattern (insert, or update if already present) is performed via single query
   ( instead of read (SQL) -> if/else (PHP) -> write (SQL) )
 - Event Handler queries are data modifying operations, so they usually won't return any result rows.
   You can, however, return results to check for plausibility in PHP after a writing query inside an
   event handler function. (or for debugging reasons)
 - Aggregations do not need to be made in PHP, which makes the result mapping code much more readable.
 - "be economical with your data" - event handler SQL queries never query "too much" data, what accidentally might happen
   when using query builders or active records.

Disadvantages:

 - To work on this package, you need to know your SQL at least to a medium level.
   - also, Postgres specific dialect and functions are used heavily 
 - This package will diverse in its "shape" from the DBAL Adapter.
 - Debugging a SQL query that performs multiple actions at once is not trivial.
   - this process needs to be well documented
 - The cognitive overhead that is reduced in PHP, moves to more complex SQL queries.
   - possible solutions for this disadvantage:
     - queries need to be **documented in detail** (both technically and use-case specific)
       - f.e. "what is the use case of updating this field?" AND "why do we use a INNER join here?"
     - also the Event Handler PHP classes (usually traits) need to be well documented
     - The respective Behavioral tests are referenced in the EventHandlers as well.
 - you have no PHP API level "domain consistency guarantees", meaning:
   "forgot a where condition in a single use-case? -> update all nodes in the table f.e."
   - that should/must be covered by tests imho.

## no query builders or string concatenation building SQL queries

Eric Kloss, 1.8.2025

The idea is, that each SQL query is coded as it is (as far as possible). No query builder PHP API should be used.

As stated before, that remove the cognitive overhead required to understand the indirections of the PHP code that builds
the actual queries for a specific use case.

Of course, almost all schema definitions depend on the **ContentRepositoryId**, this ofc. must be injected dynamically 
into the query.

Example:
```injectablephp
// this is accessible in all event handlers / read APIs
$tableNames = \Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames::create(
    \Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId::fromString('default')
)

function handleMyEvent($e): void
{
    $query = <<<SQL
        insert into {$tableNames->node()} n
        values (...)
    SQL;
}
```
!!! IMPORTANT: **never use untrusted content repository IDs as query input** as they are prune to **SQL injection**.
And, please do not call your content repository "default; truncate table users;" ;)

-> this should be impossible thou, due to the constraints in the ContentRepositoryId value object.

Advantages:

 - cognitive overhead moved to SQL
 - there is no accidental fetching of redundant data (...unless you do so in SQL)
 - The query builder pattern tends to create "bottleneck dependencies", this is avoided.
 - it is way easier to modify a single part of the whole Package, if/when:
   - the requirement for a specific use-case **changes over time**
   - there is a performance problem in a specific event handler or read API
 - in most cases, the event handlers differ in their SQL queries in small details anyway -> that might lead to the query 
   builder code to become very complex. (as the use-cases grow 2^n with the number of input parameter combinations) 
 - the query is easily debuggable by copy&pasting it to a SQL console
 - the overall structure of each event handler looks more or less the same:

```injectablephp
function handleMyEvent($e): void
{

    // ## part 1 - parameters
    $parameters = [
        // ID
        'nodeaggregateid' => $e->nodeAggregateId->value,
        'origindimensionspacepointhash' => $e->originDSP->hash,
        // Update values
        'properties' => $e->propertiesToSet->toJson(),
    ];

    // ## part 2 - query
    $query = <<<SQL
        update {$tableNames->node()}
        set properties = :properties
        where nodeaggregateid = :nodeaggregateid
          and origindimensionspacepointhash = :origindimensionspacepointhash;
    SQL;
    
    // ## part 3 - execute
    $this->getDatabaseConnection()->executeQuery($query, $parameters);
}
```

 - a more sophisticated variant could look like:

```injectablephp
function handleMyEvent($e): void
{

    // ## part 1 - parameters
    $parameters = [
        // ID
        'nodeaggregateids' => $e->nodeAggregateIdsAsStringArray ?? [],
        'origindimensionspacepointhash' => $e->originDSP->hash,
        // Update values
        'properties' => $e->propertiesToSet->toJson(),
    ];
    
    $parameterTypes = [
        // huston, we have an array
        'nodeaggregateids' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
    ];

    // ## part 2 - query
    $query = <<<SQL
        with updated_nodes as (
          update {$tableNames->node()}
          set properties = :properties
          where nodeaggregateid in (:nodeaggregateids)
            and origindimensionspacepoint = :origindimensionspacepoint;
          -- this is for plausibility checks only
          returning nodeaggregateid
        )
        -- this is for plausibility checks only
        select
            count(u.nodeaggregateid) as updated_node_count
        from updated_nodes u
    SQL;
    
    // ## part 3 - execute
    $row = $this->getDatabaseConnection()->executeQuery($query, $parameters, $parameterTypes)
        ->fetchAssociative();
    
    // ## part 4 - validate / plausibility (optional)
    if ($row['updated_node_count'] != count($e->nodeAggregateIdsAsStringArray)) {
        // here, we ensure the number of updated rows is correct
        // f.e. log or throw
        // btw... this example makes no sense use-case wise, I totally made this up ;)
        // just an example...
    }
}
```

Disadvantages:

 - There is more redundant SQL code, f.e. when a column name changes, it needs to be renamed in more occasions.
   - **this can be an advantage**, My assumptions here: 
     - A technical detail will change more often in specific use-cases,
       than it will change for all places in the system at once.
     - It is more expensive to change a detail for a specific use-case,
       if code is re-used heavily.
     - F.e. A database column rename happens fewer times than a performance tweak of a single endpoint or operation.
 - for "pure PHP devs" it is harder to work on the Package. 


## More Logic on the write-side of life ♫

Eric Kloss, 1.8.2025

Looking at the both main Read APIs: `ContentGraphInterface` and `ContentSubGraphInterface`, there is a lot of 
business logic implemented on the read-side, or - more specific - at **read-time**. 

First, I think there are two "modes" in which the Neos CR can act:

 - read heavy -> regular usage of the Neos backend editing and frontend rendering
 - write heavy -> during site imports or importers creating lots of nodes (with no rendering actions)

(I need more examples here to get a better understanding.)

F.e. looking at the function `ContentSubGraphInterface::findSubtree`:
The current approach was, to use a recursive CTE to load a subtree - well - recursively at read-time.
Recursive queries can get really slow in certain situations - most likely due to the fact, that a CTE is purely
in-memory thus can't use any indices when read from (which happens during the recursive iterations). 
Also, each iteration of the CTE needs to join the hierarchy relation as well to work with the correct node variants.

The idea to **move this logic to the write side**, could look like this:

 - we create a table called "..._subtree", which contains the whole subtree for each node variant
 - this table is basically a read-optimized, redundant store to optimize subtree lookups.
 - the read-time then never uses recursive queries, but linear scaling queries and PK index lookups.
 - for each covered node there is a single entry in this table, containing:
   - ID of the node variant -> fast lookup "get subtree for node X"
   - the subtree as flat list (varchar[]) -> for fast lookups for "give me all subtreetags with inheritance" 
     and of actual nodes "unnesting" this array
   - the tree structure as jsonb -> for conserving the depth and ordinality for ordering and tree re-construction in PHP
   - the subtree-tags for lightning fast filtering of hidden nodes (and also other excluded tags)
 - each node **write operation** that concerns the node tree, will cause a partial update of this table
   - for this operation, a query very similar to the original recursive CTE is used
   - my assumption: but is then called fewer times (way fewer)
 -> I tested this approach with a view. see this commit: https://github.com/neos/neos-development-collection/commit/5514fd68b5630b14d36c37d6492875d01627608d
   - I am aware, that the view approach is actually even worse performance than it was before, but I wanted to PoC without
     implementing partial update of this table (im currently working on that).

**BUT**: what if Neos is in write-heavy mode?

Can't we first apply all node events then after the import, update this table in a whole?

I don't know if the subtree is ever fetched in command handlers creating nodes etc. 
But I think in user-land the subtree projection must be up-to-date?
This can be implemented, let's discuss :)
-> for now, I would assume, that Neos is read-heavy more often. So default behavior is:
update the subtrees on write side every time. It shouldn't be that expensive anyway.


### Other places, where this Idea could be applied:

 - node aggregates (why is there no explicit table for node aggregates?)
 - NodeType filter and inheritance (why don't we write all inherited nodetypes to the DB?)

