============
Contributors
============

The following is a list of contributors generated from version control
information (see below). As such it is neither claiming to be complete nor is the
ordering anything but alphabetic.

* Adrian Föder
* Alexander Berl
* Andreas Förthner
* Aske Ertmann
* Bastian Waidelich
* Benno Weinzierl
* Berit Jensen
* Christian Jul Jensen
* Christian Müller
* Christopher Hlubek
* Henjo Hoeksma
* Ingmar Schlecht
* Jacob Floyd
* Karsten Dambekalns
* Marc Neuhaus
* Mario Rimann
* Markus Goldbeck
* Mattias Nilsson
* Michael Feinbier
* Nils Dehl
* Rens Admiraal
* Robert Lemke
* Sebastian Kurfürst
* Søren Malling
* Stephan Schuler
* Tobias Liebig

The list has been generated with some manual tweaking of the output of this::

  rm contributors.txt
  for REPO in `ls` ; do
    cd $REPO
    git log --format='%aN' >> ../contributors.txt
    cd ..
  done
  sort -u < contributors.txt > contributors-sorted.txt
  mv contributors-sorted.txt contributors.txt
