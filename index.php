<?php
include_once('indego.bosch.php');

/**
 * Referenzverwendung der Bosch Indego Klasse
 *
 * XXXXXX -> Username (z.B.: peter@studener.at)
 * YYYYYY -> Passwort
 */
$indego = new BoschIndego("XXXXXXXXX", "YYYYYYYYY");
$indego->getInformation();
$indego->getCalendar();
$indego->getMap();
$indego->getFirmware();
if($_GET["action"] != "") {
    $indego->doAction($_GET["action"]);
}