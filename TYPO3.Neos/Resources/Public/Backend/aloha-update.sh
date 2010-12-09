rm -Rf Aloha-Editor
git clone --recursive https://github.com/alohaeditor/Aloha-Editor.git
cd Aloha-Editor
git checkout stable
git submodule update
cd ..
find Aloha-Editor -name ".git" | xargs rm -Rf
rm Aloha-Editor/.git*
rm -Rf Aloha-Editor/build
rm -Rf Aloha-Editor/test
rm -Rf Aloha-Editor/WebContent/deps/extjs/
rm -Rf Aloha
mv Aloha-Editor Aloha
