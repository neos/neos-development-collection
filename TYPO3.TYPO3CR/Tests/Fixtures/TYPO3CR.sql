pragma auto_vacuum=0;
pragma default_cache_size=2000;
pragma encoding='UTF-8';
pragma page_size=1024;
drop table if exists [namespaces];

CREATE TABLE [namespaces] (
  [prefix] TEXT NOT NULL PRIMARY KEY, 
  [uri] TEXT NOT NULL UNIQUE);
CREATE UNIQUE INDEX [sqlite_autoindex_namespaces_2] ON [namespaces] ([uri]);

insert into [namespaces]([prefix], [uri]) values('typo3', 'http://typo3.org/ns/cms/1.0/');
insert into [namespaces]([prefix], [uri]) values('testprefix3', 'http://5-0.dev.typo3.org/test/2.0/');


drop table if exists [nodes];

CREATE TABLE [nodes] (
  [id] INTEGER NOT NULL PRIMARY KEY, 
  [name] VARCHAR(255) NOT NULL, 
  [uuid] VARCHAR(36), 
  [pid] VARCHAR(36) NOT NULL DEFAULT 0, 
  [nodetype] INTEGER);

insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(1, 'Root', '96bca35d-1ef5-4a47-8b0c-0bfc69507d00', '0', 2);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(2, 'Content', '96bca35d-1ef5-4a47-8b0c-0bfc69507d01', '96bca35d-1ef5-4a47-8b0c-0bfc69507d00', 2);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(3, 'Categories', '96fca35d-1ef5-4a47-8b0c-0bfc69507d02', '96bca35d-1ef5-4a47-8b0c-0bfc69507d01', 2);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(4, 'Pages', '96b4a35d-1ef5-4a47-8b0c-0bfc69507d03', '96fca35d-1ef5-4a47-8b0c-0bfc69507d02', 3);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(5, 'Home', '96bca35d-9ef5-4a47-8b0c-0bfc69507d05', '96b4a35d-1ef5-4a47-8b0c-0bfc69507d03', 5);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(6, 'System', '96bca35d-1ef5-4a47-8b0c-0bfc69507d06', '96bca35d-1ef5-4a47-8b0c-0bfc69507d00', 2);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(7, 'Community', '96bca35d-1ef5-4a47-8b0c-0bfc69507d04', '96bca35d-9ef5-4a47-8b0c-0bfc69507d05', 5);
insert into [nodes]([id], [name], [uuid], [pid], [nodetype]) values(8, 'News', '96bca35d-1ef5-4a47-8b0c-0bfc79507d08', '96bca35d-9ef5-4a47-8b0c-0bfc69507d05', 5);


drop table if exists [nodetypes];

CREATE TABLE [nodetypes] (
  [id] INTEGER NOT NULL PRIMARY KEY, 
  [name] VARCHAR(255));

insert into [nodetypes]([id], [name]) values(1, 'AbstractFolder');
insert into [nodetypes]([id], [name]) values(2, 'SystemFolder');
insert into [nodetypes]([id], [name]) values(3, 'Category');
insert into [nodetypes]([id], [name]) values(4, 'AbstractPage');
insert into [nodetypes]([id], [name]) values(5, 'Page');
insert into [nodetypes]([id], [name]) values(6, 'AbstractContent');


drop table if exists [properties];

CREATE TABLE [properties] (
  [nodeuuid] VARCHAR(36) NOT NULL, 
  [name] VARCHAR(255) NOT NULL, 
  [value] TEXT, 
  [namespace] VARCHAR NOT NULL DEFAULT 0, 
  [multivalue] BOOLEAN NOT NULL DEFAULT 0);

insert into [properties]([nodeuuid], [name], [value], [namespace], [multivalue]) values('96bca35d-1ef5-4a47-8b0c-0bfc69507d04', 'title', 'This page is stored in the TYPO3CR...', '0', 0);
insert into [properties]([nodeuuid], [name], [value], [namespace], [multivalue]) values('96bca35d-1ef5-4a47-8b0c-0bfc69507d04', 'subtitle', '... believe it or not!', '0', 0);
insert into [properties]([nodeuuid], [name], [value], [namespace], [multivalue]) values('96bca35d-1ef5-4a47-8b0c-0bfc79507d08', 'title', 'News about the TYPO3CR', '0', 0);
insert into [properties]([nodeuuid], [name], [value], [namespace], [multivalue]) values('96bca35d-1ef5-4a47-8b0c-0bfc79507d08', 'subtitle', 'Not much here, eh?', '0', 0);


