#!/bin/bash
if [ $(basename $(pwd)) != 'Scripts' ]; then
	echo 'This script should be called from the Scripts directory'
	exit 1
fi

# Make sure we have the latest master
if [ -d "hallo" ] && [ -d "hallo/.git" ]; then
	cd hallo && git reset --hard && git pull && cd ../
else
	rm -rf hallo
	git clone --recursive git://github.com/bergie/hallo.git hallo
fi

echo "Build hallo"
cd hallo
npm install
cake build
cake min
cd ../

echo "Move built files to Library folder"
rm -rf ../Resources/Public/Library/hallo/
mkdir ../Resources/Public/Library/hallo/
mv hallo/examples/hallo.js ../Resources/Public/Library/hallo/
mv hallo/examples/hallo-min.js ../Resources/Public/Library/hallo/
echo "Done"
