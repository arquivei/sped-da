<?php

require __DIR__ . '/../../vendor/autoload.php';

use NFePHP\DA\NFSe\Danfse;

$xml = (string) file_get_contents(__DIR__ . '/../../tests/fixtures/xml/01-06-2026_6_59326802000139.xml');

$danfse = new Danfse($xml);
$danfse->setCanhoto(true);

$pdf = $danfse->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="danfse.pdf"');
echo $pdf;
