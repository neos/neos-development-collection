#!/bin/bash

# this file is to be executed at the root of the distribution.

cd Packages/Neos/Neos.EventSourcedNeosAdjustments/Tests/Behavior
for file in $(find Features/EndToEnd -name *.feature); do
  ../../../../../bin/behat -f progress -c behat.yml.dist $file
done
