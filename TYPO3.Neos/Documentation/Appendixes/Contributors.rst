============
Contributors
============

The following is a list of contributors generated from version control
information (see below). As such it is neither claiming to be complete nor is the
ordering anything but alphabetic.

* Adrian Föder
* Alessandro Paterno
* Alexander Berl
* Alexander Frühwirth
* Alexander Kappler
* Alexander Stehlik
* Anders Pedersen
* Andreas Förthner
* Andreas Wolf
* Aske Ertmann
* Bastian Heist
* Bastian Waidelich
* Benedikt Schmitz
* Benno Weinzierl
* Berit Hlubek
* Berit Jensen
* Bernhard Schmitt
* Carsten Bleicker
* Carsten Blüm
* Cedric Ziel
* Charles Coleman
* Christian Albrecht
* Christian Jul Jensen
* Christian Müller
* Christian Vette
* Christoph Dähne
* Christopher Hlubek
* Daniel Lienert
* Denny Lubitz
* Dmitri Pisarev
* Dominik Piekarski
* Dominique Feyer
* Ernesto Baschny
* Florian Heinze
* Florian Weiss
* Frans Saris
* Franz Kugelmann
* Frederic Darmstädter
* Garvit Khatri
* Georg Ringer
* Gerhard Boden
* Hans Höchtl
* Helmut Hummel
* Henjo Hoeksma
* Ingmar Schlecht
* Irene Höppner
* Jacob Floyd
* Jacob Rasmussen
* Jan-Erik Revsbech
* Johannes Steu
* Jonas Renggli
* Jose Antonio Guerra
* Julian Kleinhans
* Kai Moeller
* Karsten Dambekalns
* Kay Strobach
* Kerstin Huppenbauer
* Kristin Povilonis
* Lars Röttig
* Lars Nieuwenhuizen
* Lienhart Woitok
* Marc Neuhaus
* Marcin Ryzycki
* Mario Rimann
* Mario Rudloff
* Markus Goldbeck
* Martin Bless
* Martin Brueggemann
* Martin Ficzel
* Martin Helmich
* Matt Gifford
* Mattias Nilsson
* Michael Feinbier
* Michael Gerdemann
* Michael Lihs
* Michiel Roos
* Moritz Spindelhirn
* Nils Dehl
* Pankaj Lele
* Patrick Reck
* Raffael Comi
* Remco Janse
* Rens Admiraal
* Robert Lemke
* Robin Poppenberg
* Roman Minchyn
* Samuel Hauser
* Sascha Nowak
* Sebastian Helzle
* Sebastian Kurfürst
* Sebastian Richter
* Sebastian Sommer
* Simon Schaufelberger
* Soeren Rohweder
* Søren Malling
* Stefan Bruggmann
* Stephan Schuler
* Thierry Brodard
* Thomas Allmer
* Thomas Hempel
* Tim Kandel
* Timo Fink
* Tobias Liebig
* Tristan Koch
* Visay Keo
* Wilhelm Behncke
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
