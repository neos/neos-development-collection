#!/bin/bash
if [ $(basename $(pwd)) != 'aloha' ]; then
	echo 'This script should be called from the Scripts/aloha directory'
	exit 1
fi

git clone -b master --recursive git://github.com/alohaeditor/Aloha-Editor.git src
./update-to-currently-working-version.sh