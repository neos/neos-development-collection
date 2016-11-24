#!/bin/bash
if [ $(basename $(pwd)) != 'vie' ]; then
	echo 'This script should be called from the Scripts/vie directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "src" ] && [ -d "src/.git" ]; then
	cd src && git reset --hard && git pull && cd ../
else
	rm -rf src
	git clone --recursive git://github.com/bergie/VIE.git src
fi
cd src
npm install
cd ../

echo "Build vie"
cd src
grunt build
cd ../

echo "Remove files we don't use"
rm -rf src/lib/jquery
rm -rf src/lib/json

echo "Move built files to Library folder"
rm -rf ../../Resources/Public/Library/vie/
mkdir ../../Resources/Public/Library/vie/
mv src/dist/vie.js ../../Resources/Public/Library/vie/
mv src/dist/vie.min.js ../../Resources/Public/Library/vie/
mv src/lib ../../Resources/Public/Library/vie/
echo "Done"