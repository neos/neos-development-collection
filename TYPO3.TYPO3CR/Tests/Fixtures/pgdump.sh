#!/bin/bash
PSQL=psql

for table in `$PSQL -U typo3v5testing -d typo3v5testbase -c '\dt' | grep "public" | awk '{print $3}'`
do
  echo Table $table:
  echo
  $PSQL -U typo3v5testing -d typo3v5testbase -c "SELECT * FROM $table;" 
done
