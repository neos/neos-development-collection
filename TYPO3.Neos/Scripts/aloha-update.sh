rm -Rf Aloha-Editor
git clone git://github.com/alohaeditor/Aloha-Editor.git

cd Aloha-Editor

./cli install
./cli version

find . -type d -name .git | xargs rm -Rf
rm .git*
rm -Rf build
rm -Rf test
rm -Rf WebContent/deps/extjs/

cd ..

rm -Rf ../Resources/Public/Backend/Aloha
mv Aloha-Editor ../Resources/Public/Backend/Aloha
