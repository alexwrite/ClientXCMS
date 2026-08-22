<?php

namespace Tests\Unit\Modules;

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Domainresellerapi\DomainresellerapiDomainRegistrar;
use App\Modules\Domainresellerapi\DomainresellerapiServiceProvider;
use App\Services\Domain\DomainRegistrarManager;
use Mockery;
use Tests\TestCase;

class DomainresellerapiDomainRegistrarTest extends TestCase
{
    private FakeDomainresellerapiClient $client;

    private DomainresellerapiDomainRegistrar $registrar;

    private array $clientArguments = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('modules/domainresellerapi/vendor/autoload.php');

        $this->client = new FakeDomainresellerapiClient;
        $this->registrar = new DomainresellerapiDomainRegistrar(function (string $username, string $password, bool $testMode) {
            $this->clientArguments = [$username, $password, $testMode];

            return $this->client;
        }, fn () => $this->server());
    }

    public function test_provider_registers_registrar(): void
    {
        $provider = new DomainresellerapiServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $registrar = app(DomainRegistrarManager::class)->get('domainresellerapi');

        $this->assertInstanceOf(DomainresellerapiDomainRegistrar::class, $registrar);
        $this->assertSame('DomainResellerAPI', $registrar->title());
    }

    public function test_server_validation_and_connection(): void
    {
        $this->assertSame('required|string|in:domainresellerapi', $this->registrar->validate()['hostname']);

        $params = array_merge($this->server()->toArray(), ['test_mode' => true]);
        $response = $this->registrar->testConnection($params);

        $this->assertTrue($response->successful());
        $this->assertSame(200, $response->status());
        $this->assertTrue($this->clientArguments[2]);
    }

    public function test_domain_availability_is_mapped(): void
    {
        $this->client->availabilityResponse = [[
            'TLD' => 'com',
            'DomainName' => 'available-domain',
            'Status' => 'available',
            'Reason' => '',
        ]];

        $availability = $this->registrar->checkAvailability('available-domain.com');

        $this->assertTrue($availability->available);
        $this->assertSame('available-domain.com', $availability->domain);
        $this->assertSame([['available-domain'], ['com'], 1, 'create'], $this->client->availabilityArguments);
    }

    public function test_registration_maps_contacts_period_privacy_and_service_data(): void
    {
        $service = $this->service(['whois_privacy' => true]);
        $this->client->registerResponse = [
            'result' => 'OK',
            'data' => [
                'ID' => 42,
                'Status' => 'Active',
                'DomainName' => 'example.com',
                'Dates' => ['Start' => '2026-08-22T10:00:00', 'Expiration' => '2028-08-22T10:00:00'],
                'NameServers' => ['NS1.EXAMPLE.NET', 'ns2.example.net'],
            ],
        ];

        $result = $this->registrar->register($service);

        $this->assertTrue($result->success);
        $this->assertSame('example.com', $this->client->registered[0]);
        $this->assertSame(2, $this->client->registered[1]);
        $this->assertTrue($this->client->registered[5]);
        $this->assertSame('Martin', $this->client->registered[2]['Registrant']['FirstName']);
        $this->assertSame('33', $this->client->registered[2]['Registrant']['PhoneCountryCode']);
        $this->assertSame('42', $service->data['registrar_id']);
        $this->assertSame('2028-08-22', $service->data['expires_at']);
        $this->assertSame(['ns1.example.net', 'ns2.example.net'], $service->data['nameservers']);
    }

    public function test_renewal_and_domain_details_are_mapped(): void
    {
        $service = $this->service();
        $this->client->renewResponse = ['result' => 'OK', 'data' => ['ExpirationDate' => '2029-08-22T10:00:00']];

        $renewed = $this->registrar->renew($service, 3);
        $domain = $this->registrar->getDomain($service);

        $this->assertTrue($renewed->success);
        $this->assertSame(['example.com', 3], $this->client->renewed);
        $this->assertSame('2029-08-22', $service->data['expires_at']);
        $this->assertSame('example.com', $domain->domain);
        $this->assertSame('Active', $domain->status);
        $this->assertSame('42', $domain->registrarId);
    }

    public function test_nameservers_are_updated_and_api_errors_are_returned(): void
    {
        $service = $this->service();

        $result = $this->registrar->updateNameservers($service, [' NS3.EXAMPLE.NET ', 'ns4.example.net']);

        $this->assertTrue($result->success);
        $this->assertSame(['ns3.example.net', 'ns4.example.net'], $service->data['nameservers']);

        $this->client->modifyNameserverResponse = ['result' => 'ERROR', 'error' => ['Message' => 'Invalid nameserver']];
        $failed = $this->registrar->updateNameservers($service, ['invalid']);
        $this->assertFalse($failed->success);
        $this->assertSame('Invalid nameserver', $failed->message);
    }

    public function test_dns_crud_uses_value_operations_and_stable_ids(): void
    {
        $service = $this->service(['dns_management' => true]);
        $this->client->records = [
            ['Name' => 'mail', 'Type' => 'MX', 'TTL' => 3600, 'Content' => '10 mx1.example.net'],
            ['Name' => 'mail', 'Type' => 'MX', 'TTL' => 3600, 'Content' => '20 mx2.example.net'],
        ];

        $records = $this->registrar->getDnsRecords($service);
        $created = $this->registrar->createDnsRecord($service, ['name' => '@', 'type' => 'A', 'value' => '192.0.2.1', 'ttl' => 600]);
        $updated = $this->registrar->updateDnsRecord($service, $records[0]['id'], ['name' => 'mail', 'type' => 'MX', 'value' => '10 mx3.example.net', 'ttl' => 7200]);
        $deleted = $this->registrar->deleteDnsRecord($service, $records[1]['id']);

        $this->assertCount(2, $records);
        $this->assertSame($records[0]['id'], $this->registrar->getDnsRecords($service)[0]['id']);
        $this->assertTrue($created->success);
        $this->assertTrue($updated->success);
        $this->assertTrue($deleted->success);
        $this->assertSame(['example.com', '', 'A', '192.0.2.1', 600], $this->client->added);
        $this->assertSame(['example.com', 'mail', 'MX', '10 mx1.example.net', '10 mx3.example.net', 7200], $this->client->replaced);
        $this->assertSame(['example.com', 'mail', 'MX', '20 mx2.example.net'], $this->client->removed);
    }

    public function test_dns_is_disabled_cleanly(): void
    {
        $service = $this->service(['dns_management' => false]);

        $this->assertSame([], $this->registrar->getDnsRecords($service));
        $result = $this->registrar->createDnsRecord($service, ['name' => '@', 'type' => 'A', 'value' => '192.0.2.1']);
        $this->assertFalse($result->success);
    }

    private function server(): Server
    {
        return new Server([
            'type' => 'domain',
            'hostname' => 'domainresellerapi',
            'address' => 'ote',
            'username' => '00000000-0000-0000-0000-000000000000',
            'password' => 'secret',
        ]);
    }

    private function service(array $data = []): Service
    {
        $service = Mockery::mock(Service::class)->makePartial();
        $service->shouldReceive('save')->andReturnTrue();
        $service->forceFill([
            'name' => 'example.com',
            'billing' => 'biennially',
            'data' => array_merge([
                'domain' => 'example.com',
                'tld' => '.com',
                'nameservers' => ['ns1.example.net', 'ns2.example.net'],
                'dns_management' => true,
            ], $data),
        ]);
        $service->setRelation('server', $this->server());
        $service->setRelation('customer', new Customer([
            'firstname' => 'Martin',
            'lastname' => 'Dupont',
            'company_name' => 'ClientXCMS',
            'email' => 'martin@example.com',
            'phone' => '06 12 34 56 78',
            'country' => 'FR',
            'address' => '1 rue de Paris',
            'address2' => 'Bâtiment A',
            'city' => 'Paris',
            'region' => 'Île-de-France',
            'zipcode' => '75001',
        ]));

        return $service;
    }
}

class FakeDomainresellerapiClient
{
    public array $availabilityResponse = [];

    public array $availabilityArguments = [];

    public array $registerResponse = ['result' => 'OK', 'data' => []];

    public array $renewResponse = ['result' => 'OK', 'data' => []];

    public array $modifyNameserverResponse = ['result' => 'OK', 'data' => []];

    public array $records = [];

    public array $registered = [];

    public array $renewed = [];

    public array $added = [];

    public array $replaced = [];

    public array $removed = [];

    public function getResellerDetails(): array
    {
        return ['result' => 'OK', 'id' => 123, 'active' => true];
    }

    public function checkAvailability(...$arguments): array
    {
        $this->availabilityArguments = $arguments;

        return $this->availabilityResponse;
    }

    public function registerWithContactInfo(...$arguments): array
    {
        $this->registered = $arguments;

        return $this->registerResponse;
    }

    public function renew(string $domain, int $years): array
    {
        $this->renewed = [$domain, $years];

        return $this->renewResponse;
    }

    public function getDetails(string $domain): array
    {
        return ['result' => 'OK', 'data' => [
            'ID' => 42,
            'Status' => 'Active',
            'DomainName' => $domain,
            'Dates' => ['Start' => '2026-08-22T10:00:00', 'Expiration' => '2029-08-22T10:00:00'],
            'NameServers' => ['ns1.example.net', 'ns2.example.net'],
        ]];
    }

    public function modifyNameServer(string $domain, array $nameservers): array
    {
        return $this->modifyNameserverResponse;
    }

    public function getResourceRecords(string $domain): array
    {
        return ['result' => 'OK', 'data' => ['records' => $this->records]];
    }

    public function addResourceRecordValue(...$arguments): array
    {
        $this->added = $arguments;

        return ['result' => 'OK'];
    }

    public function replaceResourceRecordValue(...$arguments): array
    {
        $this->replaced = $arguments;

        return ['result' => 'OK'];
    }

    public function removeResourceRecordValue(...$arguments): array
    {
        $this->removed = $arguments;

        return ['result' => 'OK'];
    }
}
