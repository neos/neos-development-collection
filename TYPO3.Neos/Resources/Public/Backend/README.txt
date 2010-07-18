This README explains how Aloha can be properly developed in the context of TYPO3.
The problem is as follows: in TYPO3 SVN, we only need the "WebContent" folder, without the "deps/extjs" folder; as ExtJS is already included in TYPO3.

However, the git repository which we use, is a fork of the official repository which can be found at http://github.com/skurfuerst/Aloha-Editor. We want to stay as close as possible to the official git repository, to improve the workflow and use new features more quickly.

That's why, if you want to develop Aloha, you need to connect SVN and git together. This is what we explain here.

1) run the following commands in the directory where you find this README.txt.
git clone http://github.com/skurfuerst/Aloha-Editor.git
mv Aloha-Editor/.git Aloha/
rm -Rf Aloha-Editor

2) Ignore .svn directories in Git, and recreate the files which are in Git, but not in SVN.
cd Aloha
echo ".svn" >> .git/info/exclude
git ls-files -d | xargs git checkout --

Now, everything is monitored by Git AND additionally, the WebContent directory is monitored by SVN.