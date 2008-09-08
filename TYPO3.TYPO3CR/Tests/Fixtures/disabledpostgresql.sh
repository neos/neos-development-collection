#!/bin/bash
PSQL=psql

if $PSQL -U postgres -c '\l' | grep "typo3v5testbase" > /dev/null; then
  $PSQL -U postgres -c "DROP DATABASE typo3v5testbase;"
fi
if $PSQL -U postgres -c '\du' | grep "typo3v5testing" > /dev/null; then
  $PSQL -U postgres -c "DROP ROLE typo3v5testing;"
fi
