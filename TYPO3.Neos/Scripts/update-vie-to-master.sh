#!/bin/bash
if [ $(basename $(pwd)) != 'Scripts' ]; then
	echo 'This script should be called from the Scripts directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "vie" ] && [ -d "vie/.git" ]; then
	cd vie && git reset --hard && git pull && cd ../
else
	rm -rf vie
	git clone --recursive git://github.com/bergie/VIE.git vie
fi

echo "Remove files we don't use"
rm -rf vie/lib/jquery
rm -rf vie/lib/json

echo "Build vie"
cd vie
ant
cd ../

echo "Move built files to Library folder"
rm -rf ../Resources/Public/Library/vie/
mkdir ../Resources/Public/Library/vie/
mv vie/dist/vie-latest.debug.js ../Resources/Public/Library/vie/
mv vie/dist/vie-latest.js ../Resources/Public/Library/vie/
mv vie/lib ../Resources/Public/Library/vie/
echo "Done"