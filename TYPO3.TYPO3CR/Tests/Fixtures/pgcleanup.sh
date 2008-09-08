#!/bin/bash
PSQL=psql

$PSQL -U postgres -c '\l' | grep "typo3v5testpostgresql" | awk '{ print $1 }' | xargs -n 1 -i{} $PSQL -U postgres -c "DROP DATABASE {}"
