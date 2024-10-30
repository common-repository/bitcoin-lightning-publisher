<?php

require 'lnd.php';

$host = '';
$tlsPath = '';
$macaroonHex = '';

$lnd = new LND();
$lnd->setHost($host);
$lnd->setMacarronHex($macaroonHex);

$info = $lnd->getInfo();

var_dump($info->{'alias'});


$invoice = $lnd->addInvoice([
  'memo' => 'PHP',
  'value' => 100 // in sats
]);
var_dump($invoice);


$lnd->getInvoice('hWV05idFvavApCYKpbYdYtfv8JQ6zhYONKOEn7ueBlY=');
$i = $lnd->getInvoice($invoice->{'r_hash'});

if ($i->{'settled'}) {
  echo 'paid';
} else {
  echo "open\n";
  echo "pay: " . $i->{'payment_request'};
}
?>
