<?php

/*
 * Copyright (C) 2014 Nikolai Neff <admin@flatplane.de>.
 *
 * This file is part of Flatplane.
 *
 * Flatplane is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or(at your option) any later version.
 *
 * Flatplane is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Flatplane.  If not, see <http://www.gnu.org/licenses/>.
 */

//use composer autoloading for dependencies
require 'flatplane.inc.php';

use de\flatplane\controller\Flatplane;

$flatplane = new Flatplane();

$flatplane::setOutputDir('output');
$flatplane::setVerboseOutput(true);


/*
 * BEGIN DOKUMENTDEFINITION
 */
//Vom Standard abweichende dokumentweite Einstellungen setzen
$settings = array(
    'author' => 'Max Mustermann',
    'title' => 'Ganz wichtiges Dokument',
    'keywords' => 'super, toll, top, gigantisch, superlative!',
    'numberingLevel' => ['list' => -1, 'formula' => -1, 'section' => -1],
    'numberingPrefix' => ['list' => '#']
);

$document = $flatplane->createDocument($settings);
$document->addSource('img:nn', ['sourceAuthor' => 'Nikolai Neff']);
$document->setPageNumberStyle(['PG1' => 'roman']);
$document->setPageNumberStyle(['PG2' => 'alpha']);
$pdf = $document->getPDF();
$pdf->setHeaderData('', 0, date('d.m.Y H:i:s'));

$inhaltSec = $document->addSection('Inhaltsverzeichnis', ['enumerate' => false]);
$inhaltSec->setShowInList(true);
$inhaltSec->setStartsNewPage(['level1' => false]);
$inhaltList = $inhaltSec->addList(['section'], ['showInList' => true]);
$abbildungSec = $document->addSection(
    'Abbildungsverzeichnis',
    ['enumerate' => false, 'showInList' => true]
);
$abbildingList = $abbildungSec->addList(['image'], ['showInList' => false]);

$einleitungSec = $document->addSection('Einleitung');
$einleitungSec->addSection('Vorwort');
$einleitungSec->addSection('Danksagungen');
$hauptteilSec = $document->addSection('Hauptteil');
$hauptteilSec->setPageGroup('PG1');
$problem = $hauptteilSec->addSection('Problemstellung');
$problem->setLabel('sec:problem');
$text1 = $problem->addText('input/testKapitelMitRef.php');

$versuch = $hauptteilSec->addSection('Versuchsaufbau');

$tex[] = '\mathcal{F}(f)(t) = \frac{1}{\left(2\pi\right)^{\frac{n}{2}}}~ \int\limits_{\mathbb{R}^n} f(x)\,e^{-\mathrm{i} t \cdot x} \,\mathrm{d} x';
$tex[] = '\int_a^b(f(x)+c)\,\mathrm dx=\int_a^b f(x)\,\mathrm dx+(b-a)\cdot c';
$tex[] = 'Z = \sum_{i=1}^{n} a_i~;~~~a_i = k_i \cdot b^i~;~~~b=2~;~~~k_i \in \{0,1\}~;~~~i\in \mathbb{N}';
$tex[] = '\overline{\overline{\left(A\, \wedge\, B\right)}\, \wedge\, C} \neq\overline{ A\, \wedge\, \overline{\left(B\, \wedge\,C \right)}}';
$tex[] = '\LaTeX ~ 2 \cdot 2 \\ 2\mathbin{\cdot}2 \\ 2 \times 2 \\ 2\mathbin{\times}2​';
$tex[] = '(\pi + \varpi) \cdot \sum_{1}^{2}{3}';
$tex[] = 'e = 2+\cfrac{1}{1+\cfrac{1}{2+\cfrac{1}{1+\cfrac{1}{1+\cfrac{1}{4+\cfrac{1}{1+\cfrac{1}{1+\cfrac{1}{6+\dotsb}}}}}}}}';

foreach (de\flatplane\documentElements\Formula::getAvailableFonts() as $key => $formulafont) {
    $versuch->addFormula($tex[$key])->setFormulaFont($formulafont);
}

foreach (de\flatplane\documentElements\Formula::getAvailableFonts() as $key => $formulafont) {
    $versuch->addFormula($tex[0])->setFormulaFont($formulafont);
}



$hauptteilSec->addSection('Versuchsdruchführung mit langen Informationen zum Umbrechen Langeswort Überschallflugzeug');
$analyse = $hauptteilSec->addSection('Datenanalyse');
$analyse->addSection('Programm A');
$programmB = $analyse->addSection('Programm B');
$bild = $programmB->addImage('images/bild.png');
$bild->setCaption('TolleSpirale '.$bild->cite('img:nn'));
$bild->setFontColor(['title' => [255, 0, 0]]);
$bild->setTitle('Roter Titel!');

$schlussSec = $document->addSection('Schluss');
$fazit = $schlussSec->addSection('Fazit');
for ($i=0; $i<10; $i++) {
    $fazit->addSection('RND'.$i.': '.mt_rand());
}
$schlussSec->addSection('ICH BIN NUR IM INHALTSVERZEICHNIS ABER NICHT IM DOKUMENT', ['showInDocument' => false]);

$schlussSec->addSection('Ausblick');
$qvz = $document->addSection('Quellenverzeichnis', ['enumerate' => false]);
$qvz->addList(['source']);

$anhangSec = $document->addSection('Anhang', ['enumerate' => false]);
$anhangSec->setPageGroup('PG2');
$document->addSection('Grundstücks­verkehrs­genehmigungs­zuständigkeits­übertragungs­verordnung (GrundVZÜV)');
$text = $anhangSec->addText('input/testKapitelohneRef.php');

$set = $document->addSection('set');
$set2 = $document->addSection('set2');
$set2->addSection('test');
$set2->setPageGroup('PG3');

//$pdf->Output('output/test.pdf', 'F');

$flatplane->generatePDF(['showDocumentTree' => false, 'clearFormulaCache' => false]);
unset($flatplane);
