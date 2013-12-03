#!/bin/bash
if [ $(basename $(pwd)) != 'hallo' ]; then
	echo 'This script should be called from the Scripts/hallo directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "src" ] && [ -d "src/.git" ]; then
	cd src && git reset --hard && git pull && cd ../
else
	rm -rf src
	git clone --recursive git://github.com/bergie/hallo.git src
fi

echo "Build hallo"
cd src
npm install
grunt build
cd ../

echo "Move built files to Library folder"
rm -rf ../../Resources/Public/Library/hallo/
mkdir ../../Resources/Public/Library/hallo/
mv src/dist/hallo.js ../../Resources/Public/Library/hallo/
mv src/dist/hallo-min.js ../../Resources/Public/Library/hallo/
echo "Done"
