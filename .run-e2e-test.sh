#!/bin/bash

# this file is to be executed at the root of the distribution.

for file in $(find Packages/CR/Neos.EventSourcedNeosAdjustments/Tests/Behavior/Features/EndToEnd -name *.feature); do
  bin/behat -f progress -c Packages/CR/Neos.EventSourcedNeosAdjustments/Tests/Behavior/behat.yml.dist $file
done
