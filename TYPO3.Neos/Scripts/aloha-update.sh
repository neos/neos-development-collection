rm -Rf Aloha-Editor
git clone git://github.com/alohaeditor/Aloha-Editor.git

cd Aloha-Editor

git checkout stable
git submodule init
find . -name .gitmodules -or -name config | while read afile; do sed -i -e 's/https:\/\/github/git:\/\/github/g' $afile; done
git submodule update
find . -type d -name .git | xargs rm -Rf
rm .git*
rm -Rf build
rm -Rf test
rm -Rf WebContent/deps/extjs/

cd ..

rm -Rf ../Resources/Public/Backend/Aloha
mv Aloha-Editor ../Resources/Public/Backend/Aloha