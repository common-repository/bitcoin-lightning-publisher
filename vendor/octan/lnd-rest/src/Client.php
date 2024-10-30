<?php

namespace LND;

use GuzzleHttp;

class Client {

  private $apiVersion = 'v1';
  private $baseURI = '';
  private $address;
  private $tlsCertificatePath = '';
  private $macaroonHex = '';
  private $client;

  public function setAddress($address) {
    $this->address = $address;
    $this->baseURI = $this->address . '/' . $this->apiVersion . '/';
  }
  public function setMacarronHex($macaroonHex) {
    $this->macaroonHex = $macaroonHex;
  }
  public function setTlsCertificatePath($path) {
    $this->tlsCertificatePath = $path;
  }

  public function isConnectionValid() {
    if (empty($this->address) || empty($this->macaroonHex)) {
      return false;
    }
    try {
      $this->getInfo();
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function getInfo() {
    return $this->request('GET', 'getinfo');
  }

  public function addInvoice($invoice) {
    return $this->request('POST', 'invoices', json_encode($invoice));
  }

  public function getInvoice($r_hash_str) {
    return $this->request('GET', 'invoice/' . bin2hex(base64_decode($r_hash_str)));
  }

  public function isInvoicePaid($r_hash_str) {
    $invoice = $this->getInvoice($r_hash_str);
    return $invoice->{'settled'};
  }

  private function request($method, $path, $body = null) {
    $headers = [
      'Grpc-Metadata-macaroon' => $this->macaroonHex,
      'Content-Type' => 'application/json'
    ];

    $request = new GuzzleHttp\Psr7\Request($method, $path, $headers, $body);
    $response = $this->client()->send($request);
    if ($response->getStatusCode() == 200) {
      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody);
    } else {
      // raise exception
    }
  }

  private function client() {
    if ($this->client) {
      return $this->client;
    }
    $options = ['base_uri' => $this->baseURI, 'timeout' => 10];
    if (!empty($this->tlsCertificatePath)) {
      $options['verify'] = $this->tlsCertificatePath;
    }
    $this->client = new GuzzleHttp\Client($options);
    return $this->client;
  }
}

?>
