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
$pdf = $document->getPDF();
$pdf->setHeaderData('', 0, date('d.m.Y H:i:s'));

//$pdf->AddPage();
//$pdf->Rect(
//    $document->getPageMargins('left'),
//    $document->getPageMargins('top'),
//    $document->getPageMeasurements()['textWidth'],
//    $document->getPageMeasurements()['textHeight'],
//    '',
//    ['all' => ['color' => [255, 0, 0]]]
//);

$inhaltSec = $document->addSection('Inhaltsverzeichnis', ['enumerate' => false]);
$inhaltSec->setShowInList(false);
$inhaltSec->setStartsNewPage(['level1' => false]);
$inhaltList = $inhaltSec->addList(['section'], ['showInList' => false]);
$abbildungSec = $document->addSection('Abbildungsverzeichnis', ['enumerate' => false, 'showInList' => false]);
$abbildingList = $abbildungSec->addList(['image'], ['showInList' => false]);

$einleitungSec = $document->addSection('Einleitung');
$einleitungSec->addSection('Vorwort');
$einleitungSec->addSection('Danksagungen');
$hauptteilSec = $document->addSection('Hauptteil');
$problem = $hauptteilSec->addSection('Problemstellung');

//$problem->addFormula('1 \ 2');
//$problem->addFormula('1 \ 2');
//$problem->addFormula('1 \ 2');
//$problem->addFormula('1 \ 2');
//$problem->addFormula('1 \ 2');
//$problem->addFormula('1 \ 2');

$versuch = $hauptteilSec->addSection('Versuchsaufbau');

//$versuch->addFormula('\pi + \varpi');
//$versuch->addFormula('\pi + \varpi');
//$versuch->addFormula('\pi + \varpi');
//$versuch->addFormula('\pi + \varpi');
//$versuch->addFormula('\pi + \varpi');

$hauptteilSec->addSection('Versuchsdruchführung mit langen Informationen zum Umbrechen Langeswort Überschallflugzeug');
$analyse = $hauptteilSec->addSection('Datenanalyse');
$analyse->addSection('Programm A');
$programmB = $analyse->addSection('Programm B');
$bild = $programmB->addImage('images/bild.png', ['caption' => 'Tolle Spirale '.$document->cite('img:nn')]);
$bild->setFontColor(['title' => [255, 0, 0]]);
$bild->setTitle('Roter Titel!');

$schlussSec = $document->addSection('Schluss');
$fazit = $schlussSec->addSection('Fazit');
for ($i=0; $i<20; $i++) {
    $fazit->addSection('RND'.$i.': '.mt_rand());
}
$schlussSec->addSection('Ausblick');
$anahngSec = $document->addSection('Anhang', ['enumerate' => false]);

$flatplane->generatePDF(['showDocumentTree' => false, 'clearFormulaCache' => false]);
unset($flatplane);
