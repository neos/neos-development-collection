#!/bin/bash
PSQL=psql

if $PSQL -U postgres -c '\l' | grep "typo3v5testbase" > /dev/null; then
  $PSQL -U postgres -c "DROP DATABASE typo3v5testbase;"
fi
if $PSQL -U postgres -c '\du' | grep "typo3v5testing" > /dev/null; then
  $PSQL -U postgres -c "DROP ROLE typo3v5testing;"
fi
$PSQL -U postgres -c "CREATE ROLE typo3v5testing PASSWORD 'typo3v5testingpass' CREATEDB LOGIN;"
$PSQL -U typo3v5testing -d template1 -c 'CREATE DATABASE typo3v5testbase;'
$PSQL -U typo3v5testing -d typo3v5testbase < TYPO3CR.sql


