
<?php

require_once 'vendor/autoload.php';

use BTCPay\Client;
use PHPUnit\Framework\TestCase;
use Dotenv;



final class EmailTest extends TestCase
{
    protected static $initialized = FALSE;

    public function setUp()
    {

        parent::setUp();

        if (!self::$initialized) {

            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
            $dotenv->safeLoad();

            self::$initialized = TRUE;
        }
    }

    private function client(): Client
    {

        return new Client($_ENV['HOST'], $_ENV['API_KEY'], $_ENV['STORE_ID']);
    }
    public function testCanBeInitialized(): void
    {
        $this->assertInstanceOf(
            Client::class,
            $this->client(),
        );
    }

    public function testCanAuthorize(): void
    {
        $client = $this->client();
        $this->assertFalse($client->isConnectionValid());
        $client->init();
        $this->assertTrue($client->isConnectionValid());
    }

    public function testCanGetBalance(): void
    {
        $client = $this->client();
        $client->init();
        $this->assertNull($client->getBalance()['balance']);
    }

    public function testCanGetInfo(): void
    {
        $client = $this->client();
        $client->init();
        $this->assertIsString($client->getInfo()['alias']);
    }

    public function testCanAddInvoice(): void
    {
        $client = $this->client();
        $client->init();
        $response = $client->addInvoice([
            'value' => 23,
            'memo' => 'test invoice'
        ]);
        $this->assertIsString($response['payment_request']);
        $this->assertIsString($response['r_hash']);
    }

    public function testCanGetInvoice(): void
    {
        $client = $this->client();
        $client->init();
        $response = $client->addInvoice([
            'value' => 23,
            'memo' => 'test invoice'
        ]);
        $invoice = $client->getInvoice($response['r_hash']);

        $this->assertArrayHasKey('settled', $invoice);
    }
}
