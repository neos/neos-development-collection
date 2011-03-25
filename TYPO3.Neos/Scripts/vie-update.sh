rm -Rf VIE-tmp
git clone git://github.com/bergie/VIE.git VIE-tmp

cd VIE-tmp

git checkout master
find . -type d -name .git | xargs rm -Rf
rm .git*
cd ..

rm -Rf ../Resources/Public/Backend/VIE
mv VIE-tmp ../Resources/Public/Backend/VIE