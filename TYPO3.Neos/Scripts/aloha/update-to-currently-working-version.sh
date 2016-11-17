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
git fetch
git checkout alohaeditor-1.2.47
git cherry-pick cb6a0a5654dc7cae088428204d4aab6cdd6dbd74
