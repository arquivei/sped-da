<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use NFePHP\DA\NFSe\Danfse;

$xml = (string) file_get_contents(__DIR__ . '/../../tests/fixtures/xml/nfse.xml');

$danfse = new Danfse($xml);
$danfse->setCanhoto(true);

$pdf = $danfse->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="danfse.pdf"');
echo $pdf;
