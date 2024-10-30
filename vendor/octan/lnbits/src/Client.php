<?php

namespace LNbits;

use GuzzleHttp;

class Client {

  private $address = 'https://legend.lnbits.com';
  private $apiKey;
  private $client;

  public function __construct($apiKey, $address = null) {
    $this->apiKey = $apiKey;
    if ($address) {
      $this->address = $address;
    }
  }

  public function setAddress($address) {
    $this->address = $address;
  }

  public function isConnectionValid() {
    return !empty($this->address) && !empty($this->apiKey);
  }

  public function getInfo() {
    $info = $this->request('GET', '/api/v1/wallet');
    $info['alias'] = $info['name'];
    $info['identity_pubkey'] = 'LNBits';
    return $info;
  }

  public function addInvoice($invoice) {
    $requestBody = [ "out" => false, "amount" => (int)$invoice['value'], "memo" => $invoice['memo'] ];
    // NOTE: unhashed_description must be hex encoded
    if (array_key_exists("unhashed_description", $invoice) && !empty($invoice["unhashed_description"])) {
      $requestBody['unhashed_description'] = bin2hex($invoice['unhashed_description']);
    }

    $invoice = $this->request('POST', '/api/v1/payments', json_encode($requestBody));
    $invoice['r_hash'] = $invoice['checking_id']; // kinda mimic lnd
    $invoice['id'] = $invoice['checking_id'];
    return $invoice;
  }

  public function getInvoice($checkingId) {
    $invoice = $this->request('GET', '/api/v1/payments/' . $checkingId);
    $invoice['settled'] = $invoice['paid']; //kinda mimic lnd
    return $invoice;
  }

  public function isInvoicePaid($checkingId) {
    $invoice = $this->getInvoice($checkingId);
    return $invoice['settled'];
  }

  private function request($method, $path, $body = null) {
    $headers = [
      'X-Api-Key' => $this->apiKey,
      'Content-Type' => 'application/json'
    ];

    $request = new GuzzleHttp\Psr7\Request($method, $path, $headers, $body);
    $response = $this->client()->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody, true);
    } else {
      // raise exception
    }
  }

  private function client() {
    if ($this->client) {
      return $this->client;
    }
    $options = ['base_uri' => $this->address, 'timeout' => 10];
    $this->client = new GuzzleHttp\Client($options);
    return $this->client;
  }
}

?>
