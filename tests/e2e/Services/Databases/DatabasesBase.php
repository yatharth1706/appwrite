<?php

namespace Tests\E2E\Services\Databases;

use Tests\E2E\Client;
use Utopia\Database\Database;
use Utopia\Database\ID;
use Utopia\Database\Permission;
use Utopia\Database\Role;

trait DatabasesBase
{
    public function testCreateDatabase(): array
    {
        /**
         * Test for SUCCESS
         */
        $database = $this->client->call(Client::METHOD_POST, '/databases', [
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ], [
            'databaseId' => ID::unique(),
            'name' => 'Test Database'
        ]);

        $this->assertNotEmpty($database['body']['$id']);
        $this->assertEquals(201, $database['headers']['status-code']);
        $this->assertEquals('Test Database', $database['body']['name']);

        return ['databaseId' => $database['body']['$id']];
    }

    /**
     * @depends testCreateDatabase
     */
    public function testCreateCollection(array $data): array
    {
        $databaseId = $data['databaseId'];
        /**
         * Test for SUCCESS
         */
        $movies = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'Movies',
            'permissions' => [
                Permission::read(Role::any()),
                Permission::create(Role::any()),
                Permission::update(Role::any()),
                Permission::delete(Role::any()),
            ],
            'documentSecurity' => true,
        ]);

        $this->assertEquals(201, $movies['headers']['status-code']);
        $this->assertEquals($movies['body']['name'], 'Movies');

        return ['moviesId' => $movies['body']['$id'], 'databaseId' => $databaseId];
    }

    /**
     * @depends testCreateCollection
     */
    public function testDisableCollection(array $data): void
    {
        $databaseId = $data['databaseId'];
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_PUT, '/databases/' . $databaseId . '/collections/' . $data['moviesId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'name' => 'Movies',
            'enabled' => false,
            'documentSecurity' => true,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertFalse($response['body']['enabled']);

        if ($this->getSide() === 'client') {
            $responseCreateDocument = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
                'content-type' => 'application/json',
                'x-appwrite-project' => $this->getProject()['$id'],
            ], $this->getHeaders()), [
                'documentId' => ID::unique(),
                'data' => [
                    'title' => 'Captain America',
                ],
                'permissions' => [
                    Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                    Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                    Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
                ],
            ]);

            $responseListDocument = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
                'content-type' => 'application/json',
                'x-appwrite-project' => $this->getProject()['$id'],
            ], $this->getHeaders()));

            $responseGetDocument = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/someID', array_merge([
                'content-type' => 'application/json',
                'x-appwrite-project' => $this->getProject()['$id'],
            ], $this->getHeaders()));

            $this->assertEquals(404, $responseCreateDocument['headers']['status-code']);
            $this->assertEquals(404, $responseListDocument['headers']['status-code']);
            $this->assertEquals(404, $responseGetDocument['headers']['status-code']);
        }

        $response = $this->client->call(Client::METHOD_PUT, '/databases/' . $databaseId . '/collections/' . $data['moviesId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'name' => 'Movies',
            'enabled' => true,
            'documentSecurity' => true,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertTrue($response['body']['enabled']);
    }


    /**
     * @depends testCreateCollection
     */
    public function testCreateAttributes(array $data): array
    {
        $databaseId = $data['databaseId'];
        $title = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/attributes/string', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'title',
            'size' => 256,
            'required' => true,
        ]);

        $releaseYear = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'releaseYear',
            'required' => true,
        ]);

        $duration = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'duration',
            'required' => false,
        ]);

        $actors = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/attributes/string', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'actors',
            'size' => 256,
            'required' => false,
            'array' => true,
        ]);

        $this->assertEquals(202, $title['headers']['status-code']);
        $this->assertEquals($title['body']['key'], 'title');
        $this->assertEquals($title['body']['type'], 'string');
        $this->assertEquals($title['body']['size'], 256);
        $this->assertEquals($title['body']['required'], true);

        $this->assertEquals(202, $releaseYear['headers']['status-code']);
        $this->assertEquals($releaseYear['body']['key'], 'releaseYear');
        $this->assertEquals($releaseYear['body']['type'], 'integer');
        $this->assertEquals($releaseYear['body']['required'], true);

        $this->assertEquals(202, $duration['headers']['status-code']);
        $this->assertEquals($duration['body']['key'], 'duration');
        $this->assertEquals($duration['body']['type'], 'integer');
        $this->assertEquals($duration['body']['required'], false);

        $this->assertEquals(202, $actors['headers']['status-code']);
        $this->assertEquals($actors['body']['key'], 'actors');
        $this->assertEquals($actors['body']['type'], 'string');
        $this->assertEquals($actors['body']['size'], 256);
        $this->assertEquals($actors['body']['required'], false);
        $this->assertEquals($actors['body']['array'], true);

        // wait for database worker to create attributes
        sleep(2);

        $movies = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), []);

        $this->assertIsArray($movies['body']['attributes']);
        $this->assertCount(4, $movies['body']['attributes']);
        $this->assertEquals($movies['body']['attributes'][0]['key'], $title['body']['key']);
        $this->assertEquals($movies['body']['attributes'][1]['key'], $releaseYear['body']['key']);
        $this->assertEquals($movies['body']['attributes'][2]['key'], $duration['body']['key']);
        $this->assertEquals($movies['body']['attributes'][3]['key'], $actors['body']['key']);

        return $data;
    }

    /**
     * @depends testCreateAttributes
     */
    public function testAttributeResponseModels(array $data): array
    {
        $databaseId = $data['databaseId'];
        $collection = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'Response Models',
            'permissions' => [],
            'documentSecurity' => true,
        ]);

        $this->assertEquals(201, $collection['headers']['status-code']);
        $this->assertEquals($collection['body']['name'], 'Response Models');

        $collectionId = $collection['body']['$id'];

        $attributesPath = "/databases/" . $databaseId . "/collections/{$collectionId}/attributes";

        $string = $this->client->call(Client::METHOD_POST, $attributesPath . '/string', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'string',
            'size' => 16,
            'required' => false,
            'default' => 'default',
        ]);

        $email = $this->client->call(Client::METHOD_POST, $attributesPath . '/email', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'email',
            'required' => false,
            'default' => 'default@example.com',
        ]);

        $enum = $this->client->call(Client::METHOD_POST, $attributesPath . '/enum', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'enum',
            'elements' => ['yes', 'no', 'maybe'],
            'required' => false,
            'default' => 'maybe',
        ]);

        $ip = $this->client->call(Client::METHOD_POST, $attributesPath . '/ip', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'ip',
            'required' => false,
            'default' => '192.0.2.0',
        ]);

        $url = $this->client->call(Client::METHOD_POST, $attributesPath . '/url', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'url',
            'required' => false,
            'default' => 'http://example.com',
        ]);

        $integer = $this->client->call(Client::METHOD_POST, $attributesPath . '/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'integer',
            'required' => false,
            'min' => 1,
            'max' => 5,
            'default' => 3
        ]);

        $float = $this->client->call(Client::METHOD_POST, $attributesPath . '/float', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'float',
            'required' => false,
            'min' => 1.5,
            'max' => 5.5,
            'default' => 3.5
        ]);

        $boolean = $this->client->call(Client::METHOD_POST, $attributesPath . '/boolean', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'boolean',
            'required' => false,
            'default' => true,
        ]);

        $this->assertEquals(202, $string['headers']['status-code']);
        $this->assertEquals('string', $string['body']['key']);
        $this->assertEquals('string', $string['body']['type']);
        $this->assertEquals(false, $string['body']['required']);
        $this->assertEquals(false, $string['body']['array']);
        $this->assertEquals(16, $string['body']['size']);
        $this->assertEquals('default', $string['body']['default']);

        $this->assertEquals(202, $email['headers']['status-code']);
        $this->assertEquals('email', $email['body']['key']);
        $this->assertEquals('string', $email['body']['type']);
        $this->assertEquals(false, $email['body']['required']);
        $this->assertEquals(false, $email['body']['array']);
        $this->assertEquals('email', $email['body']['format']);
        $this->assertEquals('default@example.com', $email['body']['default']);

        $this->assertEquals(202, $enum['headers']['status-code']);
        $this->assertEquals('enum', $enum['body']['key']);
        $this->assertEquals('string', $enum['body']['type']);
        $this->assertEquals(false, $enum['body']['required']);
        $this->assertEquals(false, $enum['body']['array']);
        $this->assertEquals('enum', $enum['body']['format']);
        $this->assertEquals('maybe', $enum['body']['default']);
        $this->assertIsArray($enum['body']['elements']);
        $this->assertEquals(['yes', 'no', 'maybe'], $enum['body']['elements']);

        $this->assertEquals(202, $ip['headers']['status-code']);
        $this->assertEquals('ip', $ip['body']['key']);
        $this->assertEquals('string', $ip['body']['type']);
        $this->assertEquals(false, $ip['body']['required']);
        $this->assertEquals(false, $ip['body']['array']);
        $this->assertEquals('ip', $ip['body']['format']);
        $this->assertEquals('192.0.2.0', $ip['body']['default']);

        $this->assertEquals(202, $url['headers']['status-code']);
        $this->assertEquals('url', $url['body']['key']);
        $this->assertEquals('string', $url['body']['type']);
        $this->assertEquals(false, $url['body']['required']);
        $this->assertEquals(false, $url['body']['array']);
        $this->assertEquals('url', $url['body']['format']);
        $this->assertEquals('http://example.com', $url['body']['default']);

        $this->assertEquals(202, $integer['headers']['status-code']);
        $this->assertEquals('integer', $integer['body']['key']);
        $this->assertEquals('integer', $integer['body']['type']);
        $this->assertEquals(false, $integer['body']['required']);
        $this->assertEquals(false, $integer['body']['array']);
        $this->assertEquals(1, $integer['body']['min']);
        $this->assertEquals(5, $integer['body']['max']);
        $this->assertEquals(3, $integer['body']['default']);

        $this->assertEquals(202, $float['headers']['status-code']);
        $this->assertEquals('float', $float['body']['key']);
        $this->assertEquals('double', $float['body']['type']);
        $this->assertEquals(false, $float['body']['required']);
        $this->assertEquals(false, $float['body']['array']);
        $this->assertEquals(1.5, $float['body']['min']);
        $this->assertEquals(5.5, $float['body']['max']);
        $this->assertEquals(3.5, $float['body']['default']);

        $this->assertEquals(202, $boolean['headers']['status-code']);
        $this->assertEquals('boolean', $boolean['body']['key']);
        $this->assertEquals('boolean', $boolean['body']['type']);
        $this->assertEquals(false, $boolean['body']['required']);
        $this->assertEquals(false, $boolean['body']['array']);
        $this->assertEquals(true, $boolean['body']['default']);

        // wait for database worker to create attributes
        sleep(30);

        $stringResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $string['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $emailResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $email['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $enumResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $enum['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $ipResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $ip['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $urlResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $url['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $integerResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $integer['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $floatResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $float['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $booleanResponse = $this->client->call(Client::METHOD_GET, $attributesPath . '/' . $boolean['body']['key'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $this->assertEquals(200, $stringResponse['headers']['status-code']);
        $this->assertEquals($string['body']['key'], $stringResponse['body']['key']);
        $this->assertEquals($string['body']['type'], $stringResponse['body']['type']);
        $this->assertEquals('available', $stringResponse['body']['status']);
        $this->assertEquals($string['body']['required'], $stringResponse['body']['required']);
        $this->assertEquals($string['body']['array'], $stringResponse['body']['array']);
        $this->assertEquals(16, $stringResponse['body']['size']);
        $this->assertEquals($string['body']['default'], $stringResponse['body']['default']);

        $this->assertEquals(200, $emailResponse['headers']['status-code']);
        $this->assertEquals($email['body']['key'], $emailResponse['body']['key']);
        $this->assertEquals($email['body']['type'], $emailResponse['body']['type']);
        $this->assertEquals('available', $emailResponse['body']['status']);
        $this->assertEquals($email['body']['required'], $emailResponse['body']['required']);
        $this->assertEquals($email['body']['array'], $emailResponse['body']['array']);
        $this->assertEquals($email['body']['format'], $emailResponse['body']['format']);
        $this->assertEquals($email['body']['default'], $emailResponse['body']['default']);

        $this->assertEquals(200, $enumResponse['headers']['status-code']);
        $this->assertEquals($enum['body']['key'], $enumResponse['body']['key']);
        $this->assertEquals($enum['body']['type'], $enumResponse['body']['type']);
        $this->assertEquals('available', $enumResponse['body']['status']);
        $this->assertEquals($enum['body']['required'], $enumResponse['body']['required']);
        $this->assertEquals($enum['body']['array'], $enumResponse['body']['array']);
        $this->assertEquals($enum['body']['format'], $enumResponse['body']['format']);
        $this->assertEquals($enum['body']['default'], $enumResponse['body']['default']);
        $this->assertEquals($enum['body']['elements'], $enumResponse['body']['elements']);

        $this->assertEquals(200, $ipResponse['headers']['status-code']);
        $this->assertEquals($ip['body']['key'], $ipResponse['body']['key']);
        $this->assertEquals($ip['body']['type'], $ipResponse['body']['type']);
        $this->assertEquals('available', $ipResponse['body']['status']);
        $this->assertEquals($ip['body']['required'], $ipResponse['body']['required']);
        $this->assertEquals($ip['body']['array'], $ipResponse['body']['array']);
        $this->assertEquals($ip['body']['format'], $ipResponse['body']['format']);
        $this->assertEquals($ip['body']['default'], $ipResponse['body']['default']);

        $this->assertEquals(200, $urlResponse['headers']['status-code']);
        $this->assertEquals($url['body']['key'], $urlResponse['body']['key']);
        $this->assertEquals($url['body']['type'], $urlResponse['body']['type']);
        $this->assertEquals('available', $urlResponse['body']['status']);
        $this->assertEquals($url['body']['required'], $urlResponse['body']['required']);
        $this->assertEquals($url['body']['array'], $urlResponse['body']['array']);
        $this->assertEquals($url['body']['format'], $urlResponse['body']['format']);
        $this->assertEquals($url['body']['default'], $urlResponse['body']['default']);

        $this->assertEquals(200, $integerResponse['headers']['status-code']);
        $this->assertEquals($integer['body']['key'], $integerResponse['body']['key']);
        $this->assertEquals($integer['body']['type'], $integerResponse['body']['type']);
        $this->assertEquals('available', $integerResponse['body']['status']);
        $this->assertEquals($integer['body']['required'], $integerResponse['body']['required']);
        $this->assertEquals($integer['body']['array'], $integerResponse['body']['array']);
        $this->assertEquals($integer['body']['min'], $integerResponse['body']['min']);
        $this->assertEquals($integer['body']['max'], $integerResponse['body']['max']);
        $this->assertEquals($integer['body']['default'], $integerResponse['body']['default']);

        $this->assertEquals(200, $floatResponse['headers']['status-code']);
        $this->assertEquals($float['body']['key'], $floatResponse['body']['key']);
        $this->assertEquals($float['body']['type'], $floatResponse['body']['type']);
        $this->assertEquals('available', $floatResponse['body']['status']);
        $this->assertEquals($float['body']['required'], $floatResponse['body']['required']);
        $this->assertEquals($float['body']['array'], $floatResponse['body']['array']);
        $this->assertEquals($float['body']['min'], $floatResponse['body']['min']);
        $this->assertEquals($float['body']['max'], $floatResponse['body']['max']);
        $this->assertEquals($float['body']['default'], $floatResponse['body']['default']);

        $this->assertEquals(200, $booleanResponse['headers']['status-code']);
        $this->assertEquals($boolean['body']['key'], $booleanResponse['body']['key']);
        $this->assertEquals($boolean['body']['type'], $booleanResponse['body']['type']);
        $this->assertEquals('available', $booleanResponse['body']['status']);
        $this->assertEquals($boolean['body']['required'], $booleanResponse['body']['required']);
        $this->assertEquals($boolean['body']['array'], $booleanResponse['body']['array']);
        $this->assertEquals($boolean['body']['default'], $booleanResponse['body']['default']);

        $attributes = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $this->assertEquals(200, $attributes['headers']['status-code']);
        $this->assertEquals(8, $attributes['body']['total']);

        $attributes = $attributes['body']['attributes'];

        $this->assertIsArray($attributes);
        $this->assertCount(8, $attributes);

        $this->assertEquals($stringResponse['body']['key'], $attributes[0]['key']);
        $this->assertEquals($stringResponse['body']['type'], $attributes[0]['type']);
        $this->assertEquals($stringResponse['body']['status'], $attributes[0]['status']);
        $this->assertEquals($stringResponse['body']['required'], $attributes[0]['required']);
        $this->assertEquals($stringResponse['body']['array'], $attributes[0]['array']);
        $this->assertEquals($stringResponse['body']['size'], $attributes[0]['size']);
        $this->assertEquals($stringResponse['body']['default'], $attributes[0]['default']);

        $this->assertEquals($emailResponse['body']['key'], $attributes[1]['key']);
        $this->assertEquals($emailResponse['body']['type'], $attributes[1]['type']);
        $this->assertEquals($emailResponse['body']['status'], $attributes[1]['status']);
        $this->assertEquals($emailResponse['body']['required'], $attributes[1]['required']);
        $this->assertEquals($emailResponse['body']['array'], $attributes[1]['array']);
        $this->assertEquals($emailResponse['body']['default'], $attributes[1]['default']);
        $this->assertEquals($emailResponse['body']['format'], $attributes[1]['format']);

        $this->assertEquals($enumResponse['body']['key'], $attributes[2]['key']);
        $this->assertEquals($enumResponse['body']['type'], $attributes[2]['type']);
        $this->assertEquals($enumResponse['body']['status'], $attributes[2]['status']);
        $this->assertEquals($enumResponse['body']['required'], $attributes[2]['required']);
        $this->assertEquals($enumResponse['body']['array'], $attributes[2]['array']);
        $this->assertEquals($enumResponse['body']['default'], $attributes[2]['default']);
        $this->assertEquals($enumResponse['body']['format'], $attributes[2]['format']);
        $this->assertEquals($enumResponse['body']['elements'], $attributes[2]['elements']);

        $this->assertEquals($ipResponse['body']['key'], $attributes[3]['key']);
        $this->assertEquals($ipResponse['body']['type'], $attributes[3]['type']);
        $this->assertEquals($ipResponse['body']['status'], $attributes[3]['status']);
        $this->assertEquals($ipResponse['body']['required'], $attributes[3]['required']);
        $this->assertEquals($ipResponse['body']['array'], $attributes[3]['array']);
        $this->assertEquals($ipResponse['body']['default'], $attributes[3]['default']);
        $this->assertEquals($ipResponse['body']['format'], $attributes[3]['format']);

        $this->assertEquals($urlResponse['body']['key'], $attributes[4]['key']);
        $this->assertEquals($urlResponse['body']['type'], $attributes[4]['type']);
        $this->assertEquals($urlResponse['body']['status'], $attributes[4]['status']);
        $this->assertEquals($urlResponse['body']['required'], $attributes[4]['required']);
        $this->assertEquals($urlResponse['body']['array'], $attributes[4]['array']);
        $this->assertEquals($urlResponse['body']['default'], $attributes[4]['default']);
        $this->assertEquals($urlResponse['body']['format'], $attributes[4]['format']);

        $this->assertEquals($integerResponse['body']['key'], $attributes[5]['key']);
        $this->assertEquals($integerResponse['body']['type'], $attributes[5]['type']);
        $this->assertEquals($integerResponse['body']['status'], $attributes[5]['status']);
        $this->assertEquals($integerResponse['body']['required'], $attributes[5]['required']);
        $this->assertEquals($integerResponse['body']['array'], $attributes[5]['array']);
        $this->assertEquals($integerResponse['body']['default'], $attributes[5]['default']);
        $this->assertEquals($integerResponse['body']['min'], $attributes[5]['min']);
        $this->assertEquals($integerResponse['body']['max'], $attributes[5]['max']);

        $this->assertEquals($floatResponse['body']['key'], $attributes[6]['key']);
        $this->assertEquals($floatResponse['body']['type'], $attributes[6]['type']);
        $this->assertEquals($floatResponse['body']['status'], $attributes[6]['status']);
        $this->assertEquals($floatResponse['body']['required'], $attributes[6]['required']);
        $this->assertEquals($floatResponse['body']['array'], $attributes[6]['array']);
        $this->assertEquals($floatResponse['body']['default'], $attributes[6]['default']);
        $this->assertEquals($floatResponse['body']['min'], $attributes[6]['min']);
        $this->assertEquals($floatResponse['body']['max'], $attributes[6]['max']);

        $this->assertEquals($booleanResponse['body']['key'], $attributes[7]['key']);
        $this->assertEquals($booleanResponse['body']['type'], $attributes[7]['type']);
        $this->assertEquals($booleanResponse['body']['status'], $attributes[7]['status']);
        $this->assertEquals($booleanResponse['body']['required'], $attributes[7]['required']);
        $this->assertEquals($booleanResponse['body']['array'], $attributes[7]['array']);
        $this->assertEquals($booleanResponse['body']['default'], $attributes[7]['default']);

        $collection = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]));

        $this->assertEquals(200, $collection['headers']['status-code']);

        $attributes = $collection['body']['attributes'];

        $this->assertIsArray($attributes);
        $this->assertCount(8, $attributes);

        $this->assertEquals($stringResponse['body']['key'], $attributes[0]['key']);
        $this->assertEquals($stringResponse['body']['type'], $attributes[0]['type']);
        $this->assertEquals($stringResponse['body']['status'], $attributes[0]['status']);
        $this->assertEquals($stringResponse['body']['required'], $attributes[0]['required']);
        $this->assertEquals($stringResponse['body']['array'], $attributes[0]['array']);
        $this->assertEquals($stringResponse['body']['size'], $attributes[0]['size']);
        $this->assertEquals($stringResponse['body']['default'], $attributes[0]['default']);

        $this->assertEquals($emailResponse['body']['key'], $attributes[1]['key']);
        $this->assertEquals($emailResponse['body']['type'], $attributes[1]['type']);
        $this->assertEquals($emailResponse['body']['status'], $attributes[1]['status']);
        $this->assertEquals($emailResponse['body']['required'], $attributes[1]['required']);
        $this->assertEquals($emailResponse['body']['array'], $attributes[1]['array']);
        $this->assertEquals($emailResponse['body']['default'], $attributes[1]['default']);
        $this->assertEquals($emailResponse['body']['format'], $attributes[1]['format']);

        $this->assertEquals($enumResponse['body']['key'], $attributes[2]['key']);
        $this->assertEquals($enumResponse['body']['type'], $attributes[2]['type']);
        $this->assertEquals($enumResponse['body']['status'], $attributes[2]['status']);
        $this->assertEquals($enumResponse['body']['required'], $attributes[2]['required']);
        $this->assertEquals($enumResponse['body']['array'], $attributes[2]['array']);
        $this->assertEquals($enumResponse['body']['default'], $attributes[2]['default']);
        $this->assertEquals($enumResponse['body']['format'], $attributes[2]['format']);
        $this->assertEquals($enumResponse['body']['elements'], $attributes[2]['elements']);

        $this->assertEquals($ipResponse['body']['key'], $attributes[3]['key']);
        $this->assertEquals($ipResponse['body']['type'], $attributes[3]['type']);
        $this->assertEquals($ipResponse['body']['status'], $attributes[3]['status']);
        $this->assertEquals($ipResponse['body']['required'], $attributes[3]['required']);
        $this->assertEquals($ipResponse['body']['array'], $attributes[3]['array']);
        $this->assertEquals($ipResponse['body']['default'], $attributes[3]['default']);
        $this->assertEquals($ipResponse['body']['format'], $attributes[3]['format']);

        $this->assertEquals($urlResponse['body']['key'], $attributes[4]['key']);
        $this->assertEquals($urlResponse['body']['type'], $attributes[4]['type']);
        $this->assertEquals($urlResponse['body']['status'], $attributes[4]['status']);
        $this->assertEquals($urlResponse['body']['required'], $attributes[4]['required']);
        $this->assertEquals($urlResponse['body']['array'], $attributes[4]['array']);
        $this->assertEquals($urlResponse['body']['default'], $attributes[4]['default']);
        $this->assertEquals($urlResponse['body']['format'], $attributes[4]['format']);

        $this->assertEquals($integerResponse['body']['key'], $attributes[5]['key']);
        $this->assertEquals($integerResponse['body']['type'], $attributes[5]['type']);
        $this->assertEquals($integerResponse['body']['status'], $attributes[5]['status']);
        $this->assertEquals($integerResponse['body']['required'], $attributes[5]['required']);
        $this->assertEquals($integerResponse['body']['array'], $attributes[5]['array']);
        $this->assertEquals($integerResponse['body']['default'], $attributes[5]['default']);
        $this->assertEquals($integerResponse['body']['min'], $attributes[5]['min']);
        $this->assertEquals($integerResponse['body']['max'], $attributes[5]['max']);

        $this->assertEquals($floatResponse['body']['key'], $attributes[6]['key']);
        $this->assertEquals($floatResponse['body']['type'], $attributes[6]['type']);
        $this->assertEquals($floatResponse['body']['status'], $attributes[6]['status']);
        $this->assertEquals($floatResponse['body']['required'], $attributes[6]['required']);
        $this->assertEquals($floatResponse['body']['array'], $attributes[6]['array']);
        $this->assertEquals($floatResponse['body']['default'], $attributes[6]['default']);
        $this->assertEquals($floatResponse['body']['min'], $attributes[6]['min']);
        $this->assertEquals($floatResponse['body']['max'], $attributes[6]['max']);

        $this->assertEquals($booleanResponse['body']['key'], $attributes[7]['key']);
        $this->assertEquals($booleanResponse['body']['type'], $attributes[7]['type']);
        $this->assertEquals($booleanResponse['body']['status'], $attributes[7]['status']);
        $this->assertEquals($booleanResponse['body']['required'], $attributes[7]['required']);
        $this->assertEquals($booleanResponse['body']['array'], $attributes[7]['array']);
        $this->assertEquals($booleanResponse['body']['default'], $attributes[7]['default']);

        /**
         * Test for FAILURE
         */
        $badEnum = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/enum', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'enum',
            'elements' => ['yes', 'no', ''],
            'required' => false,
            'default' => 'maybe',
        ]);

        $this->assertEquals(400, $badEnum['headers']['status-code']);
        $this->assertEquals('Each enum element must not be empty', $badEnum['body']['message']);

        return $data;
    }

    /**
     * @depends testCreateAttributes
     */
    public function testCreateIndexes(array $data): array
    {
        $databaseId = $data['databaseId'];
        $titleIndex = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/indexes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'titleIndex',
            'type' => 'fulltext',
            'attributes' => ['title'],
        ]);

        $this->assertEquals(202, $titleIndex['headers']['status-code']);
        $this->assertEquals('titleIndex', $titleIndex['body']['key']);
        $this->assertEquals('fulltext', $titleIndex['body']['type']);
        $this->assertCount(1, $titleIndex['body']['attributes']);
        $this->assertEquals('title', $titleIndex['body']['attributes'][0]);

        $releaseYearIndex = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/indexes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'releaseYear',
            'type' => 'key',
            'attributes' => ['releaseYear'],
        ]);

        $this->assertEquals(202, $releaseYearIndex['headers']['status-code']);
        $this->assertEquals('releaseYear', $releaseYearIndex['body']['key']);
        $this->assertEquals('key', $releaseYearIndex['body']['type']);
        $this->assertCount(1, $releaseYearIndex['body']['attributes']);
        $this->assertEquals('releaseYear', $releaseYearIndex['body']['attributes'][0]);

        $releaseWithDate = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/indexes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'releaseYearDated',
            'type' => 'key',
            'attributes' => ['releaseYear', '$createdAt', '$updatedAt'],
        ]);

        $this->assertEquals(202, $releaseWithDate['headers']['status-code']);
        $this->assertEquals('releaseYearDated', $releaseWithDate['body']['key']);
        $this->assertEquals('key', $releaseWithDate['body']['type']);
        $this->assertCount(3, $releaseWithDate['body']['attributes']);
        $this->assertEquals('releaseYear', $releaseWithDate['body']['attributes'][0]);
        $this->assertEquals('$createdAt', $releaseWithDate['body']['attributes'][1]);
        $this->assertEquals('$updatedAt', $releaseWithDate['body']['attributes'][2]);

        // wait for database worker to create index
        sleep(2);

        $movies = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), []);

        $this->assertIsArray($movies['body']['indexes']);
        $this->assertCount(3, $movies['body']['indexes']);
        $this->assertEquals($titleIndex['body']['key'], $movies['body']['indexes'][0]['key']);
        $this->assertEquals($releaseYearIndex['body']['key'], $movies['body']['indexes'][1]['key']);
        $this->assertEquals($releaseWithDate['body']['key'], $movies['body']['indexes'][2]['key']);
        $this->assertEquals('available', $movies['body']['indexes'][0]['status']);
        $this->assertEquals('available', $movies['body']['indexes'][1]['status']);
        $this->assertEquals('available', $movies['body']['indexes'][2]['status']);

        return $data;
    }

    /**
     * @depends testCreateIndexes
     */
    public function testCreateDocument(array $data): array
    {
        $databaseId = $data['databaseId'];
        $document1 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America',
                'releaseYear' => 1944,
                'actors' => [
                    'Chris Evans',
                    'Samuel Jackson',
                ]
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $document2 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Spider-Man: Far From Home',
                'releaseYear' => 2019,
                'actors' => [
                    'Tom Holland',
                    'Zendaya Maree Stoermer',
                    'Samuel Jackson',
                ]
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $document3 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Spider-Man: Homecoming',
                'releaseYear' => 2017,
                'duration' => 0,
                'actors' => [
                    'Tom Holland',
                    'Zendaya Maree Stoermer',
                ],
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $document4 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'releaseYear' => 2020, // Missing title, expect an 400 error
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(201, $document1['headers']['status-code']);
        $this->assertEquals($document1['body']['title'], 'Captain America');
        $this->assertEquals($document1['body']['releaseYear'], 1944);
        $this->assertIsArray($document1['body']['$permissions']);
        $this->assertCount(3, $document1['body']['$permissions']);
        $this->assertCount(2, $document1['body']['actors']);
        $this->assertEquals($document1['body']['actors'][0], 'Chris Evans');
        $this->assertEquals($document1['body']['actors'][1], 'Samuel Jackson');

        $this->assertEquals(201, $document2['headers']['status-code']);
        $this->assertEquals($document2['body']['title'], 'Spider-Man: Far From Home');
        $this->assertEquals($document2['body']['releaseYear'], 2019);
        $this->assertEquals($document2['body']['duration'], null);
        $this->assertIsArray($document2['body']['$permissions']);
        $this->assertCount(3, $document2['body']['$permissions']);
        $this->assertCount(3, $document2['body']['actors']);
        $this->assertEquals($document2['body']['actors'][0], 'Tom Holland');
        $this->assertEquals($document2['body']['actors'][1], 'Zendaya Maree Stoermer');
        $this->assertEquals($document2['body']['actors'][2], 'Samuel Jackson');

        $this->assertEquals(201, $document3['headers']['status-code']);
        $this->assertEquals($document3['body']['title'], 'Spider-Man: Homecoming');
        $this->assertEquals($document3['body']['releaseYear'], 2017);
        $this->assertEquals($document3['body']['duration'], 0);
        $this->assertIsArray($document3['body']['$permissions']);
        $this->assertCount(3, $document3['body']['$permissions']);
        $this->assertCount(2, $document3['body']['actors']);
        $this->assertEquals($document3['body']['actors'][0], 'Tom Holland');
        $this->assertEquals($document3['body']['actors'][1], 'Zendaya Maree Stoermer');

        $this->assertEquals(400, $document4['headers']['status-code']);

        return $data;
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocuments(array $data): array
    {
        $databaseId = $data['databaseId'];
        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][2]['releaseYear']);
        $this->assertFalse(array_key_exists('$internalId', $documents['body']['documents'][0]));
        $this->assertFalse(array_key_exists('$internalId', $documents['body']['documents'][1]));
        $this->assertFalse(array_key_exists('$internalId', $documents['body']['documents'][2]));
        $this->assertCount(3, $documents['body']['documents']);

        foreach ($documents['body']['documents'] as $document) {
            $this->assertEquals($data['moviesId'], $document['$collection']);
        }

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['DESC'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(1944, $documents['body']['documents'][2]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(3, $documents['body']['documents']);

        return ['documents' => $documents['body']['documents'], 'databaseId' => $databaseId];
    }

    public function testCreateCollectionAlias(): array
    {
        // Create default database
        $database = $this->client->call(Client::METHOD_POST, '/databases', [
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ], [
            'databaseId' => ID::custom('default'),
            'name' => 'Default'
        ]);

        $this->assertNotEmpty($database['body']['$id']);
        $this->assertEquals(201, $database['headers']['status-code']);

        /**
         * Test for SUCCESS
         */

        $movies = $this->client->call(Client::METHOD_POST, '/database/collections', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'Movies',
            'permissions' => [],
            'documentSecurity' => true,
        ]);

        $this->assertEquals(201, $movies['headers']['status-code']);
        $this->assertEquals('Movies', $movies['body']['name']);

        return ['moviesId' => $movies['body']['$id']];
    }

    /**
     * @depends testCreateCollectionAlias
     */
    public function testListDocumentsAlias(array $data): array
    {
        /**
         * Test for SUCCESS
         */

        $documents = $this->client->call(Client::METHOD_GET, '/database/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(0, $documents['body']['total']);

        return [];
    }

    /**
     * @depends testListDocuments
     */
    public function testGetDocument(array $data): void
    {
        $databaseId = $data['databaseId'];
        foreach ($data['documents'] as $document) {
            $response = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $document['$collection'] . '/documents/' . $document['$id'], array_merge([
                'content-type' => 'application/json',
                'x-appwrite-project' => $this->getProject()['$id'],
            ], $this->getHeaders()));

            $this->assertEquals(200, $response['headers']['status-code']);
            $this->assertEquals($response['body']['$id'], $document['$id']);
            $this->assertEquals($response['body']['$collection'], $document['$collection']);
            $this->assertEquals($response['body']['title'], $document['title']);
            $this->assertEquals($response['body']['releaseYear'], $document['releaseYear']);
            $this->assertEquals($response['body']['$permissions'], $document['$permissions']);
            $this->assertFalse(array_key_exists('$internalId', $response['body']));
        }
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsAfterPagination(array $data): array
    {
        $databaseId = $data['databaseId'];
        /**
         * Test after without order.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals('Captain America', $base['body']['documents'][0]['title']);
        $this->assertEquals('Spider-Man: Far From Home', $base['body']['documents'][1]['title']);
        $this->assertEquals('Spider-Man: Homecoming', $base['body']['documents'][2]['title']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'cursor' => $base['body']['documents'][0]['$id']
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][1]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertEquals($base['body']['documents'][2]['$id'], $documents['body']['documents'][1]['$id']);
        $this->assertCount(2, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'cursor' => $base['body']['documents'][2]['$id']
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEmpty($documents['body']['documents']);

        /**
         * Test with ASC order and after.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
        ]);

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals(1944, $base['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $base['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $base['body']['documents'][2]['releaseYear']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
            'cursor' => $base['body']['documents'][1]['$id']
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][2]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertCount(1, $documents['body']['documents']);

        /**
         * Test with DESC order and after.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['DESC'],
        ]);

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals(1944, $base['body']['documents'][2]['releaseYear']);
        $this->assertEquals(2017, $base['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $base['body']['documents'][0]['releaseYear']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['DESC'],
            'cursor' => $base['body']['documents'][1]['$id']
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][2]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertCount(1, $documents['body']['documents']);

        /**
         * Test after with unknown document.
         */
        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'cursor' => 'unknown'
        ]);

        $this->assertEquals(400, $documents['headers']['status-code']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsBeforePagination(array $data): array
    {
        $databaseId = $data['databaseId'];
        /**
         * Test before without order.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals('Captain America', $base['body']['documents'][0]['title']);
        $this->assertEquals('Spider-Man: Far From Home', $base['body']['documents'][1]['title']);
        $this->assertEquals('Spider-Man: Homecoming', $base['body']['documents'][2]['title']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'cursor' => $base['body']['documents'][2]['$id'],
            'cursorDirection' => Database::CURSOR_BEFORE
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][0]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertEquals($base['body']['documents'][1]['$id'], $documents['body']['documents'][1]['$id']);
        $this->assertCount(2, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'cursor' => $base['body']['documents'][0]['$id'],
            'cursorDirection' => Database::CURSOR_BEFORE
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEmpty($documents['body']['documents']);

        /**
         * Test with ASC order and after.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
        ]);

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals(1944, $base['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $base['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $base['body']['documents'][2]['releaseYear']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
            'cursor' => $base['body']['documents'][1]['$id'],
            'cursorDirection' => Database::CURSOR_BEFORE
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][0]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertCount(1, $documents['body']['documents']);

        /**
         * Test with DESC order and after.
         */
        $base = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['DESC'],
        ]);

        $this->assertEquals(200, $base['headers']['status-code']);
        $this->assertEquals(1944, $base['body']['documents'][2]['releaseYear']);
        $this->assertEquals(2017, $base['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $base['body']['documents'][0]['releaseYear']);
        $this->assertCount(3, $base['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['DESC'],
            'cursor' => $base['body']['documents'][1]['$id'],
            'cursorDirection' => Database::CURSOR_BEFORE
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals($base['body']['documents'][0]['$id'], $documents['body']['documents'][0]['$id']);
        $this->assertCount(1, $documents['body']['documents']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsLimitAndOffset(array $data): array
    {
        $databaseId = $data['databaseId'];
        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'limit' => 1,
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'limit' => 2,
            'offset' => 1,
            'orderAttributes' => ['releaseYear'],
            'orderTypes' => ['ASC'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(2017, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][1]['releaseYear']);
        $this->assertCount(2, $documents['body']['documents']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testDocumentsListQueries(array $data): array
    {
        $databaseId = $data['databaseId'];
        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['title.search("Captain America")'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['$id.equal("' . $documents['body']['documents'][0]['$id'] . '")'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['title.search("Homecoming")'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(2017, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['title.search("spider")'],
        ]);

        $this->assertEquals(200, $documents['headers']['status-code']);
        $this->assertEquals(2019, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertCount(2, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['releaseYear.equal(1944)'],
        ]);

        $this->assertCount(1, $documents['body']['documents']);
        $this->assertEquals('Captain America', $documents['body']['documents'][0]['title']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['releaseYear.notEqual(1944)'],
        ]);

        $this->assertCount(2, $documents['body']['documents']);
        $this->assertEquals('Spider-Man: Far From Home', $documents['body']['documents'][0]['title']);
        $this->assertEquals('Spider-Man: Homecoming', $documents['body']['documents'][1]['title']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['$createdAt.greater(132)'],
        ]);

        $this->assertCount(3, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['$createdAt.lesser(132)'],
        ]);

        $this->assertCount(0, $documents['body']['documents']);

        /**
         * Test for Failure
         */
        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['actors.equal("Tom Holland")'],
        ]);
        $this->assertEquals(400, $documents['headers']['status-code']);
        $this->assertEquals('Index not found: actors', $documents['body']['message']);

        $conditions = [];

        for ($i = 0; $i < 101; $i++) {
            $conditions[] = $i;
        }

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['releaseYear.equal(' . implode(',', $conditions) . ')'],
        ]);

        $this->assertEquals(400, $documents['headers']['status-code']);

        $conditions = [];

        for ($i = 0; $i < 101; $i++) {
            $conditions[] = "[" . $i . "] Too long title to cross 2k chars query limit";
        }

        $documents = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'queries' => ['title.search(' . implode(',', $conditions) . ')'],
        ]);

        $this->assertEquals(400, $documents['headers']['status-code']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testUpdateDocument(array $data): array
    {
        $databaseId = $data['databaseId'];
        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Thor: Ragnaroc',
                'releaseYear' => 2017,
                'actors' => [],
                '$createdAt' => 5 // Should be ignored
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ],
        ]);

        $id = $document['body']['$id'];

        $this->assertEquals(201, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Thor: Ragnaroc');
        $this->assertEquals($document['body']['releaseYear'], 2017);
        $this->assertNotEquals($document['body']['$createdAt'], 5);
        $this->assertContains('read(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
        $this->assertContains('update(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
        $this->assertContains('delete(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);

        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'data' => [
                'title' => 'Thor: Ragnarok',
            ],
            'permissions' => [
                Permission::read(Role::users()),
                Permission::update(Role::users()),
                Permission::delete(Role::users()),
            ],
        ]);

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertEquals($document['body']['$id'], $id);
        $this->assertEquals($document['body']['$collection'], $data['moviesId']);
        $this->assertEquals($document['body']['title'], 'Thor: Ragnarok');
        $this->assertEquals($document['body']['releaseYear'], 2017);
        $this->assertContains('read(users)', $document['body']['$permissions']);
        $this->assertContains('update(users)', $document['body']['$permissions']);
        $this->assertContains('delete(users)', $document['body']['$permissions']);

        $document = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $id = $document['body']['$id'];

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Thor: Ragnarok');
        $this->assertEquals($document['body']['releaseYear'], 2017);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testDeleteDocument(array $data): array
    {
        $databaseId = $data['databaseId'];
        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Thor: Ragnarok',
                'releaseYear' => 2017,
                'actors' => [],
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $id = $document['body']['$id'];

        $this->assertEquals(201, $document['headers']['status-code']);

        $document = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $document['headers']['status-code']);

        $document = $this->client->call(Client::METHOD_DELETE, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(204, $document['headers']['status-code']);

        $document = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(404, $document['headers']['status-code']);

        return $data;
    }

    public function testInvalidDocumentStructure()
    {
        $database = $this->client->call(Client::METHOD_POST, '/databases', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'databaseId' => ID::unique(),
            'name' => 'InvalidDocumentDatabase',
        ]);
        $this->assertEquals(201, $database['headers']['status-code']);
        $this->assertEquals('InvalidDocumentDatabase', $database['body']['name']);

        $databaseId = $database['body']['$id'];
        $collection = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'invalidDocumentStructure',
            'permissions' => [
                Permission::create(Role::any()),
                Permission::read(Role::any()),
            ],
            'documentSecurity' => true,
        ]);

        $this->assertEquals(201, $collection['headers']['status-code']);
        $this->assertEquals('invalidDocumentStructure', $collection['body']['name']);

        $collectionId = $collection['body']['$id'];

        $email = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/email', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'email',
            'required' => false,
        ]);

        $enum = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/enum', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'enum',
            'elements' => ['yes', 'no', 'maybe'],
            'required' => false,
        ]);

        $ip = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/ip', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'ip',
            'required' => false,
        ]);

        $url = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/url', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'url',
            'size' => 256,
            'required' => false,
        ]);

        $range = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'range',
            'required' => false,
            'min' => 1,
            'max' => 10,
        ]);

        // TODO@kodumbeats min and max are rounded in error message
        $floatRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/float', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'floatRange',
            'required' => false,
            'min' => 1.1,
            'max' => 1.4,
        ]);

        $probability = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/float', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'probability',
            'required' => false,
            'default' => 0,
            'min' => 0,
            'max' => 1,
        ]);

        $upperBound = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'upperBound',
            'required' => false,
            'max' => 10,
        ]);

        $lowerBound = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'lowerBound',
            'required' => false,
            'min' => 5,
        ]);

        /**
         * Test for failure
         */

        $invalidRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json', 'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'invalidRange',
            'required' => false,
            'min' => 4,
            'max' => 3,
        ]);

        $defaultArray = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json', 'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'defaultArray',
            'required' => false,
            'default' => 42,
            'array' => true,
        ]);

        $defaultRequired = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/integer', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'attributeId' => ID::custom('defaultRequired'),
            'required' => true,
            'default' => 12
        ]);

        $enumDefault = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/enum', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'attributeId' => ID::custom('enumDefault'),
            'elements' => ['north', 'west'],
            'default' => 'south'
        ]);

        $enumDefaultStrict = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/enum', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'attributeId' => ID::custom('enumDefault'),
            'elements' => ['north', 'west'],
            'default' => 'NORTH'
        ]);

        $this->assertEquals(202, $email['headers']['status-code']);
        $this->assertEquals(202, $ip['headers']['status-code']);
        $this->assertEquals(202, $url['headers']['status-code']);
        $this->assertEquals(202, $range['headers']['status-code']);
        $this->assertEquals(202, $floatRange['headers']['status-code']);
        $this->assertEquals(202, $probability['headers']['status-code']);
        $this->assertEquals(202, $upperBound['headers']['status-code']);
        $this->assertEquals(202, $lowerBound['headers']['status-code']);
        $this->assertEquals(202, $enum['headers']['status-code']);
        $this->assertEquals(400, $invalidRange['headers']['status-code']);
        $this->assertEquals(400, $defaultArray['headers']['status-code']);
        $this->assertEquals(400, $defaultRequired['headers']['status-code']);
        $this->assertEquals(400, $enumDefault['headers']['status-code']);
        $this->assertEquals(400, $enumDefaultStrict['headers']['status-code']);
        $this->assertEquals('Minimum value must be lesser than maximum value', $invalidRange['body']['message']);
        $this->assertEquals('Cannot set default value for array attributes', $defaultArray['body']['message']);

        // wait for worker to add attributes
        sleep(3);

        $collection = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey'],
        ]), []);

        $this->assertCount(9, $collection['body']['attributes']);

        /**
         * Test for successful validation
         */

        $goodEmail = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'email' => 'user@example.com',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodEnum = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'enum' => 'yes',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodIp = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'ip' => '1.1.1.1',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodUrl = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'url' => 'http://www.example.com',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'range' => 3,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodFloatRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'floatRange' => 1.4,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $goodProbability = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'probability' => 0.99999,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $notTooHigh = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'upperBound' => 8,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $notTooLow = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'lowerBound' => 8,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(201, $goodEmail['headers']['status-code']);
        $this->assertEquals(201, $goodEnum['headers']['status-code']);
        $this->assertEquals(201, $goodIp['headers']['status-code']);
        $this->assertEquals(201, $goodUrl['headers']['status-code']);
        $this->assertEquals(201, $goodRange['headers']['status-code']);
        $this->assertEquals(201, $goodFloatRange['headers']['status-code']);
        $this->assertEquals(201, $goodProbability['headers']['status-code']);
        $this->assertEquals(201, $notTooHigh['headers']['status-code']);
        $this->assertEquals(201, $notTooLow['headers']['status-code']);

        /*
         * Test that custom validators reject documents
         */

        $badEmail = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'email' => 'user@@example.com',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badEnum = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'enum' => 'badEnum',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badIp = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'ip' => '1.1.1.1.1',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badUrl = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'url' => 'example...com',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'range' => 11,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badFloatRange = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'floatRange' => 2.5,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $badProbability = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'probability' => 1.1,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $tooHigh = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'upperBound' => 11,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $tooLow = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'lowerBound' => 3,
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(400, $badEmail['headers']['status-code']);
        $this->assertEquals(400, $badEnum['headers']['status-code']);
        $this->assertEquals(400, $badIp['headers']['status-code']);
        $this->assertEquals(400, $badUrl['headers']['status-code']);
        $this->assertEquals(400, $badRange['headers']['status-code']);
        $this->assertEquals(400, $badFloatRange['headers']['status-code']);
        $this->assertEquals(400, $badProbability['headers']['status-code']);
        $this->assertEquals(400, $tooHigh['headers']['status-code']);
        $this->assertEquals(400, $tooLow['headers']['status-code']);
        $this->assertEquals('Invalid document structure: Attribute "email" has invalid format. Value must be a valid email address', $badEmail['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "enum" has invalid format. Value must be one of (yes, no, maybe)', $badEnum['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "ip" has invalid format. Value must be a valid IP address', $badIp['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "url" has invalid format. Value must be a valid URL', $badUrl['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "range" has invalid format. Value must be a valid range between 1 and 10', $badRange['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "floatRange" has invalid format. Value must be a valid range between 1 and 1', $badFloatRange['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "probability" has invalid format. Value must be a valid range between 0 and 1', $badProbability['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "upperBound" has invalid format. Value must be a valid range between -9,223,372,036,854,775,808 and 10', $tooHigh['body']['message']);
        $this->assertEquals('Invalid document structure: Attribute "lowerBound" has invalid format. Value must be a valid range between 5 and 9,223,372,036,854,775,808', $tooLow['body']['message']);
    }

    /**
     * @depends testDeleteDocument
     */
    public function testDefaultPermissions(array $data): array
    {
        $databaseId = $data['databaseId'];
        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America',
                'releaseYear' => 1944,
                'actors' => [],
            ],
        ]);

        $id = $document['body']['$id'];

        $this->assertEquals(201, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Captain America');
        $this->assertEquals($document['body']['releaseYear'], 1944);
        $this->assertIsArray($document['body']['$permissions']);

        if ($this->getSide() == 'client') {
            $this->assertCount(3, $document['body']['$permissions']);
            $this->assertContains('read(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
            $this->assertContains('update(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
            $this->assertContains('delete(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
        }

        if ($this->getSide() == 'server') {
            $this->assertCount(0, $document['body']['$permissions']);
            $this->assertEquals([], $document['body']['$permissions']);
        }

        // Updated and Inherit Permissions

        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'data' => [
                'title' => 'Captain America 2',
                'releaseYear' => 1945,
                'actors' => [],
            ],
            'permissions' => [
                Permission::read(Role::any()),
            ],
        ]);

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Captain America 2');
        $this->assertEquals($document['body']['releaseYear'], 1945);

        // This differs from the old permissions model because we don't inherit
        // existing document permissions on update, unless none were supplied,
        // so that specific types can be removed if wanted.
        $this->assertCount(1, $document['body']['$permissions']);
        $this->assertContains('read(any)', $document['body']['$permissions']);

        $document = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Captain America 2');
        $this->assertEquals($document['body']['releaseYear'], 1945);

        // This differs from the old permissions model because we don't inherit
        // existing document permissions on update, unless none were supplied,
        // so that specific types can be removed if wanted.
        $this->assertCount(1, $document['body']['$permissions']);
        $this->assertContains('read(any)', $document['body']['$permissions']);

        // Reset Permissions

        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'data' => [
                'title' => 'Captain America 3',
                'releaseYear' => 1946,
                'actors' => [],
            ],
            'permissions' => [],
        ]);

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertEquals($document['body']['title'], 'Captain America 3');
        $this->assertEquals($document['body']['releaseYear'], 1946);
        $this->assertCount(0, $document['body']['$permissions']);
        $this->assertEquals([], $document['body']['$permissions']);

        return $data;
    }

    public function testEnforceDocumentPermissions(): void
    {
        $database = $this->client->call(Client::METHOD_POST, '/databases', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'databaseId' => ID::unique(),
            'name' => 'EnforceCollectionPermissions',
        ]);
        $this->assertEquals(201, $database['headers']['status-code']);
        $this->assertEquals('EnforceCollectionPermissions', $database['body']['name']);

        $databaseId = $database['body']['$id'];
        $user = 'user:' . $this->getUser()['$id'];
        $collection = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'enforceCollectionPermissions',
            'documentSecurity' => true,
            'permissions' => [
                'read(' . $user . ')',
                'create(' . $user . ')',
                'update(' . $user . ')',
                'delete(' . $user . ')',
            ],
        ]);

        $this->assertEquals(201, $collection['headers']['status-code']);
        $this->assertEquals($collection['body']['name'], 'enforceCollectionPermissions');
        $this->assertEquals($collection['body']['documentSecurity'], true);

        $collectionId = $collection['body']['$id'];

        sleep(2);

        $attribute = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/attributes/string', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'attribute',
            'size' => 64,
            'required' => true,
        ]);

        $this->assertEquals(202, $attribute['headers']['status-code'], 202);
        $this->assertEquals('attribute', $attribute['body']['key']);

        // wait for db to add attribute
        sleep(2);

        $index = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/indexes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'key_attribute',
            'type' => 'key',
            'attributes' => [$attribute['body']['key']],
        ]);

        $this->assertEquals(202, $index['headers']['status-code']);
        $this->assertEquals('key_attribute', $index['body']['key']);

        // wait for db to add attribute
        sleep(2);

        $document1 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'attribute' => 'one',
            ],
            'permissions' => [
                'read(' . $user . ')',
                'update(' . $user . ')',
                'delete(' . $user . ')',
            ]
        ]);

        $this->assertEquals(201, $document1['headers']['status-code']);

        $document2 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'attribute' => 'one',
            ],
            'permissions' => [
                'update(' . $user . ')',
                'delete(' . $user . ')',
            ]
        ]);

        $this->assertEquals(201, $document2['headers']['status-code']);

        $document3 = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', [
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ], [
            'documentId' => ID::unique(),
            'data' => [
                'attribute' => 'one',
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom('other'))),
                Permission::update(Role::user(ID::custom('other'))),
            ],
        ]);

        $this->assertEquals(201, $document3['headers']['status-code']);

        $documentsUser1 = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        switch ($this->getSide()) {
            case 'client':
                $this->assertEquals(2, $documentsUser1['body']['total']);
                $this->assertCount(2, $documentsUser1['body']['documents']);
                break;
            case 'server':
                $this->assertEquals(3, $documentsUser1['body']['total']);
                $this->assertCount(3, $documentsUser1['body']['documents']);
                break;
        }

        $document3GetWithCollectionRead = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents/' . $document3['body']['$id'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $document3GetWithCollectionRead['headers']['status-code']);

        $email = uniqid() . 'user@localhost.test';
        $password = 'password';
        $name = 'User Name';
        $this->client->call(Client::METHOD_POST, '/account', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], [
            'userId' => ID::custom('other'),
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ]);
        $session2 = $this->client->call(Client::METHOD_POST, '/account/sessions/email', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], [
            'email' => $email,
            'password' => $password,
        ]);
        $session2 = $this->client->parseCookie((string)$session2['headers']['set-cookie'])['a_session_' . $this->getProject()['$id']];

        $document3GetWithDocumentRead = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents/' . $document3['body']['$id'], [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_' . $this->getProject()['$id'] . '=' . $session2,
        ]);

        $this->assertEquals(200, $document3GetWithDocumentRead['headers']['status-code']);

        $document2GetFailure = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents/' . $document2['body']['$id'], [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_' . $this->getProject()['$id'] . '=' . $session2,
        ]);

        $this->assertEquals(401, $document2GetFailure['headers']['status-code']);

        $documentsUser2 = $this->client->call(Client::METHOD_GET, '/databases/' . $databaseId . '/collections/' . $collectionId . '/documents', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_' . $this->getProject()['$id'] . '=' . $session2,
        ]);

        $this->assertEquals(1, $documentsUser2['body']['total']);
        $this->assertCount(1, $documentsUser2['body']['documents']);
    }

    /**
     * @depends testDefaultPermissions
     */
    public function testUniqueIndexDuplicate(array $data): array
    {
        $databaseId = $data['databaseId'];
        $uniqueIndex = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/indexes', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'unique_title',
            'type' => 'unique',
            'attributes' => ['title'],
        ]);

        $this->assertEquals(202, $uniqueIndex['headers']['status-code']);

        sleep(2);

        // test for failure
        $duplicate = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America',
                'releaseYear' => 1944,
                'actors' => [
                    'Chris Evans',
                    'Samuel Jackson',
                ]
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(409, $duplicate['headers']['status-code']);

        // Test for exception when updating document to conflict
        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America 5',
                'releaseYear' => 1944,
                'actors' => [
                    'Chris Evans',
                    'Samuel Jackson',
                ]
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(201, $document['headers']['status-code']);

        // Test for exception when updating document to conflict
        $duplicate = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $data['moviesId'] . '/documents/' . $document['body']['$id'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America',
                'releaseYear' => 1944,
                'actors' => [
                    'Chris Evans',
                    'Samuel Jackson',
                ]
            ],
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(409, $duplicate['headers']['status-code']);

        return $data;
    }

    /**
     * @depends testUniqueIndexDuplicate
     */
    public function testPersistantCreatedAt(array $data): array
    {
        $headers = $this->getSide() === 'client' ? array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()) : [
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ];

        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $data['databaseId'] . '/collections/' . $data['moviesId'] . '/documents', $headers, [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Creation Date Test',
                'releaseYear' => 2000
            ]
        ]);

        $this->assertEquals($document['body']['title'], 'Creation Date Test');

        $documentId = $document['body']['$id'];
        $createdAt = $document['body']['$createdAt'];
        $updatedAt = $document['body']['$updatedAt'];

        \sleep(1);

        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $data['databaseId'] . '/collections/' . $data['moviesId'] . '/documents/' . $documentId, $headers, [
            'data' => [
                'title' => 'Updated Date Test',
            ]
        ]);

        $updatedAtSecond = $document['body']['$updatedAt'];

        $this->assertEquals($document['body']['title'], 'Updated Date Test');
        $this->assertEquals($document['body']['$createdAt'], $createdAt);
        $this->assertNotEquals($document['body']['$updatedAt'], $updatedAt);

        \sleep(1);

        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $data['databaseId'] . '/collections/' . $data['moviesId'] . '/documents/' . $documentId, $headers, [
            'data' => [
                'title' => 'Again Updated Date Test',
                '$createdAt' => 1657271810, // Try to update it, should not work
                '$updatedAt' => 1657271810 // Try to update it, should not work
            ]
        ]);

        $this->assertEquals($document['body']['title'], 'Again Updated Date Test');
        $this->assertEquals($document['body']['$createdAt'], $createdAt);
        $this->assertNotEquals($document['body']['$updatedAt'], $updatedAt);
        $this->assertNotEquals($document['body']['$updatedAt'], $updatedAtSecond);
        $this->assertNotEquals($document['body']['$updatedAt'], 1657271810);

        return $data;
    }

    public function testUpdatePermissionsWithEmptyPayload(): array
    {
        // Create Database
        $database = $this->client->call(Client::METHOD_POST, '/databases', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'databaseId' => ID::unique(),
            'name' => 'Empty Permissions',
        ]);
        $this->assertEquals(201, $database['headers']['status-code']);

        $databaseId = $database['body']['$id'];

        // Create collection
        $movies = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'collectionId' => ID::unique(),
            'name' => 'Movies',
            'permissions' => [
                Permission::create(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ],
            'documentSecurity' => true,
        ]);

        $this->assertEquals(201, $movies['headers']['status-code']);
        $this->assertEquals($movies['body']['name'], 'Movies');

        $moviesId = $movies['body']['$id'];

        // create attribute
        $title = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $moviesId . '/attributes/string', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'key' => 'title',
            'size' => 256,
            'required' => true,
        ]);

        $this->assertEquals(202, $title['headers']['status-code']);

        // wait for database worker to create attributes
        sleep(2);

        // add document
        $document = $this->client->call(Client::METHOD_POST, '/databases/' . $databaseId . '/collections/' . $moviesId . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'documentId' => ID::unique(),
            'data' => [
                'title' => 'Captain America',
            ],
            'permissions' => [
                Permission::read(Role::any()),
                Permission::update(Role::any()),
                Permission::delete(Role::any()),
            ],
        ]);

        $id = $document['body']['$id'];

        $this->assertEquals(201, $document['headers']['status-code']);
        $this->assertCount(3, $document['body']['$permissions']);
        $this->assertContains('read(any)', $document['body']['$permissions']);
        $this->assertContains('update(any)', $document['body']['$permissions']);
        $this->assertContains('delete(any)', $document['body']['$permissions']);

        // Send only read permission
        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $moviesId . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'permissions' => [
                Permission::read(Role::user(ID::custom($this->getUser()['$id']))),
            ]
        ]);

        $this->assertEquals(200, $document['headers']['status-code']);
        $this->assertCount(1, $document['body']['$permissions']);

        // Send only mutation permissions
        $document = $this->client->call(Client::METHOD_PATCH, '/databases/' . $databaseId . '/collections/' . $moviesId . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'permissions' => [
                Permission::update(Role::user(ID::custom($this->getUser()['$id']))),
                Permission::delete(Role::user(ID::custom($this->getUser()['$id']))),
            ],
        ]);

        if ($this->getSide() == 'server') {
            $this->assertEquals(200, $document['headers']['status-code']);
            $this->assertCount(2, $document['body']['$permissions']);
            $this->assertContains('update(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
            $this->assertContains('delete(user:' . $this->getUser()['$id'] . ')', $document['body']['$permissions']);
        }

        // remove collection
        $this->client->call(Client::METHOD_DELETE, '/databases/' . $databaseId . '/collections/' . $moviesId, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        return [];
    }
}
