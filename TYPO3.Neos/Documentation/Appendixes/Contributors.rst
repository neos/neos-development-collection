============
Contributors
============

The following is a list of contributors generated from version control
information (see below). As such it is neither claiming to be complete nor is the
ordering anything but alphabetic.

* Adrian Föder
* Alexander Berl
* Alexander Stehlik
* Anders Pedersen
* Andreas Förthner
* Andreas Wolf
* Aske Ertmann
* Bastian Waidelich
* Benno Weinzierl
* Berit Hlubek
* Carsten Bleicker
* Christian Albrecht
* Christian Jul Jensen
* Christian Müller
* Christoph Dähne
* Christopher Hlubek
* Daniel Lienert
* Dmitri Pisarev
* Dominik Piekarski
* Dominique Feyer
* Ernesto Baschny
* Florian Heinze
* Frans Saris
* Garvit Khatri
* Georg Ringer
* Hans Höchtl
* Helmut Hummel
* Henjo Hoeksma
* Ingmar Schlecht
* Irene Höppner
* Jacob Floyd
* Jacob Rasmussen
* Jan-Erik Revsbech
* Jonas Renggli
* Jose Antonio Guerra
* Julian Kleinhans
* Kai Moeller
* Karsten Dambekalns
* Kerstin Huppenbauer
* Lars Röttig
* Lars Nieuwenhuizen
* Lienhart Woitok
* Marc Neuhaus
* Marcin Ryzycki
* Mario Rimann
* Markus Goldbeck
* Martin Bless
* Martin Brueggemann
* Mattias Nilsson
* Michael Feinbier
* Michael Gerdemann
* Michael Lihs
* Michiel Roos
* Nils Dehl
* Pankaj Lele
* Rens Admiraal
* Robert Lemke
* Sascha Nowak
* Sebastian Helzle
* Sebastian Kurfürst
* Simon Schaufelberger
* Soeren Rohweder
* Søren Malling
* Stephan Schuler
* Thomas Allmer
* Thomas Hempel
* Tim Kandel
* Tobias Liebig
* Visay Keo
* Wouter Wolters

The list has been generated with some manual tweaking of the output of this script ``contributors.sh`` executed in
``Packages/Application``::

  rm -f contributors.txt
  for REPO in `ls` ; do
    if [ -d "$REPO" ]; then
      cd $REPO
      git log --format='%aN' >> ../contributors.txt
      cd ..
    fi
  done
  sort -u < contributors.txt > contributors-sorted.txt
  mv contributors-sorted.txt contributors.txt
