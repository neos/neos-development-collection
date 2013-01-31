#!/bin/bash
if [ $(basename $(pwd)) != 'Scripts' ]; then
	echo 'This script should be called from the Scripts directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "create" ] && [ -d "create/.git" ]; then
	cd create && git reset --hard && git pull && cd ../
else
	rm -rf create
	git clone --recursive git://github.com/bergie/create.git create
fi

echo "Remove files we don't use"
rm -rf create/src/jquery.Midgard.midgardNotifications.js
rm -rf create/src/jquery.Midgard.midgardToolbar.js
rm -rf create/src/jquery.Midgard.midgardTags.js
rm -rf create/src/editingWidgets/jquery.Midgard.midgardEditableEditorRedactor.js
rm -rf create/deps/backbone-min.js
rm -rf create/deps/hallo-min.js
rm -rf create/deps/jquery-*.js
rm -rf create/deps/underscore-min.js
rm -rf create/deps/vie-min.js

echo "Build create"
cd create
npm install
cake build
cake min
cd ../

echo "Move built files to Library folder"
rm -rf ../Resources/Public/Library/createjs/
mkdir ../Resources/Public/Library/createjs/
mv create/examples/create.js ../Resources/Public/Library/createjs/
mv create/examples/create-min.js ../Resources/Public/Library/createjs/
mv create/deps ../Resources/Public/Library/createjs/
echo "Done"