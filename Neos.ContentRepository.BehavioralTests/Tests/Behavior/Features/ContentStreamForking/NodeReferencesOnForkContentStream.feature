@fixtures @adapters=DoctrineDBAL
Feature: On forking a content stream, node references should be copied as well.

  Because we store reference node information in an extra DB table, this needs to be copied correctly on ForkContentStream
  as well.

  TODO implement test case and fix implementation
