<?php

namespace LNDHub;

require_once "contracts/LndHubClient.php";

use \GuzzleHttp;
use LNDHub\Contracts\LNDHubClient;

class Client implements LNDHubClient
{
  private $client;
  private $access_token;
  private $refresh_token;
  private $url;
  private $login;
  private $password;

  public function __construct($url, $login, $password)
  {
    $this->url = $url;
    $this->login = $login;
    $this->password = $password;
  }

  // deprecated
  public function init()
  {
    return true;
  }

  private function authorize()
  {
    // if we got an access token we assume it works.
    if (!empty($this->access_token)) {
      return;
    }
    $headers = [
      "Accept" => "application/json",
      "Content-Type" => "application/json",
      "Access-Control-Allow-Origin" => "*",
      "User-Agent" => "lndhub-php",
    ];
    $body = ["login" => $this->login, "password" => $this->password];
    $request = new GuzzleHttp\Psr7\Request(
      "POST",
      "/auth?type=auth",
      $headers,
      json_encode($body)
    );
    $response = $this->client()->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, true);
      $this->access_token = $data["access_token"];
      $this->refresh_token = $data["refresh_token"];
      return $data;
    } else {
      // raise exception
    }
  }

  private function request($method, $path, $body = null)
  {
    // make sure we have an access token
    $this->authorize();

    $headers = [
      "Accept" => "application/json",
      "Content-Type" => "application/json",
      "Access-Control-Allow-Origin" => "*",
      "Authorization" => "Bearer {$this->access_token}",
      "User-Agent" => "lndhub-php",
    ];

    $requestBody = $body ? json_encode($body) : null;
    $request = new GuzzleHttp\Psr7\Request(
      $method,
      $path,
      $headers,
      $requestBody
    );
    $response = $this->client()->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody, true);
    } else {
      // raise exception
    }
  }

  public function getInfo(): array
  {
    $data = $this->request("GET", "/getinfo");
    return $data;
  }

  public function getBalance()
  {
    $data = $this->request("GET", "/balance");
    $data["balance"] = $data["BTC"]["AvailableBalance"];
    return $data;
  }

  private function client()
  {
    if ($this->client) {
      return $this->client;
    }
    $options = ["base_uri" => $this->url, 'timeout' => 10];
    $this->client = new GuzzleHttp\Client($options);
    return $this->client;
  }

  public function isConnectionValid(): bool
  {
    try {
      // make sure we have an access token
      $this->authorize();
      return !empty($this->access_token);
    } catch(\Exception $e) {
      return false;
    }
  }

  public function addInvoice($invoice): array
  {
    $params = [ "amt" => $invoice["value"], "memo" => $invoice["memo"] ];
    if (array_key_exists("description_hash", $invoice) && !empty($invoice["description_hash"])) {
      $params['description_hash'] = $invoice['description_hash'];
    }
    $data = $this->request("POST", "/addinvoice", $params);
    if (
      is_array($data) &&
      is_array($data["r_hash"]) &&
      $data["r_hash"]["type"] === "Buffer"
    ) {
      $data["r_hash"] = bin2hex(
        join(array_map("chr", $data["r_hash"]["data"]))
      );
    }
    $data["id"] = $data["r_hash"];
    return $data;
  }

  public function getInvoice($rHash): array
  {
    $invoice = $this->request("GET", "/checkpayment/{$rHash}");

    $invoice["settled"] = $invoice["paid"] ? true : false; //kinda mimic lnd
    return $invoice;
  }

  public function isInvoicePaid($rHash): bool
  {
    $invoice = $this->getInvoice($rHash);
    return $invoice["settled"];
  }

  public static function createWallet($url, $partnerId, $accountType = "common")
  {
    $headers = [
      "Accept" => "application/json",
      "Content-Type" => "application/json",
      "Access-Control-Allow-Origin" => "*",
    ];
    $body = ["lpartnerid" => $partnerId, "accounttype" => $accountType];
    $request = new GuzzleHttp\Psr7\Request(
      "POST",
      "/create",
      $headers,
      json_encode($body)
    );
    $client = new GuzzleHttp\Client(["base_uri" => $url]);
    $response = $client->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, true);
      return array_merge($data, ["url" => $url]);
    } else {
      // raise exception
    }
  }

  public static function createAlbyWallet($email, $password)
  {
    $headers = [
      "Accept" => "application/json",
      "Content-Type" => "application/json",
      "Access-Control-Allow-Origin" => "*",
      "User-Agent" => "lndhub-php",
    ];
    $body = ["email" => $email, "password" => $password];
    $request = new GuzzleHttp\Psr7\Request(
      "POST",
      "/api/users",
      $headers,
      json_encode($body)
    );
    $client = new GuzzleHttp\Client(["base_uri" => "https://getalby.com"]);
    $response = $client->send($request);
    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);
    return $data;
  }
}
