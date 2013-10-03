#!/bin/bash
if [ $(basename $(pwd)) != 'aloha' ]; then
	echo 'This script should be called from the Scripts/aloha directory'
	exit 1
fi

if [ ! -d "src" ]; then
	echo 'No src folder yet, call init.sh first'
	exit 1
fi

cd src
git checkout face03bd261c282dac8cc84955e9173787a9548f