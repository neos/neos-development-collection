./update-to-currently-working-version.sh

BUILD_SCRIPT_DIR=`pwd`

rm -rf ../../Resources/Public/Library/aloha/
mkdir -p ../../Resources/Public/Library/aloha/css

cd src

# JS
node build/r.js -o $BUILD_SCRIPT_DIR/build-profile-with-common-extra-plugins.js

cmd="sed -i "
if [[ $(uname -a) =~ "Darwin" ]]; then
	cmd="sed -i '' "
fi
# We need to patch this file to make Aloha compatible with jquery UI 10.3
$cmd "s/this.container.tabs('select', this.index);/this.container.tabs('option', 'active', this.index);/g" target/build-profile-with-common-extra-plugins/rjs-output/optimized-aloha.js

# HOTFIX: enable Aloha in Chrome as well.
$cmd 's/(browser.webkit && parseFloat(version) < 532.5)/(browser.webkit \&\& parseFloat(version) < 532.5 \&\& !browser.chrome)/' target/build-profile-with-common-extra-plugins/rjs-output/optimized-aloha.js

cp target/build-profile-with-common-extra-plugins/rjs-output/optimized-aloha.js ../../../Resources/Public/Library/aloha/aloha.js

# CSS
mvn generate-sources
cp src/css/aloha.css ../../../Resources/Public/Library/aloha/css/

cd src
# Copy images over
for file in `find . -name "*.png" -o -name "*.gif" -o -name "*.cur"`; do
	mkdir -p ../../../../Resources/Public/Library/aloha/`dirname $file` && cp $file ../../../../Resources/Public/Library/aloha/$file
done
cd ..

rm -Rf ../../../Resources/Public/Library/aloha/demo/
