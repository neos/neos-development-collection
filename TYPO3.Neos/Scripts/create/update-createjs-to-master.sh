#!/bin/bash
if [ $(basename $(pwd)) != 'create' ]; then
	echo 'This script should be called from the Scripts/create directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "src" ] && [ -d "src/.git" ]; then
	cd src && git reset --hard && git pull && cd ../
else
	rm -rf src
	git clone --recursive git://github.com/bergie/create.git src
fi

echo "Remove files we don't use"
rm -rf src/src/jquery.Midgard.midgardNotifications.js
rm -rf src/src/jquery.Midgard.midgardToolbar.js
rm -rf src/src/jquery.Midgard.midgardTags.js
rm -rf src/src/editingWidgets/jquery.Midgard.midgardEditableEditorRedactor.js
rm -rf src/deps/backbone-min.js
rm -rf src/deps/hallo-min.js
rm -rf src/deps/jquery-*.js
rm -rf src/deps/underscore-min.js
rm -rf src/deps/vie-min.js

echo "Build create"
cd src
npm install
grunt build
cd ../

echo "Move built files to Library folder"
rm -rf ../../Resources/Public/Library/createjs/
mkdir -p ../../Resources/Public/Library/createjs/deps/
mv src/dist/create.js ../../Resources/Public/Library/createjs/
mv src/dist/create.min.js ../../Resources/Public/Library/createjs/
echo "Done"