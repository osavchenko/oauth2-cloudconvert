<?php

namespace Osavchenko\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Osavchenko\OAuth2\Client\Provider\CloudConvert;
use Osavchenko\OAuth2\Client\Provider\CloudConvertResourceOwner;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\ClientInterface;

final class CloudConvertTest extends TestCase
{
    use QueryBuilderTrait;

    protected CloudConvert $provider;

    protected function setUp(): void
    {
        $this->provider = new CloudConvert([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }


    public function testScopes(): void
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);

        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken(): void
    {
        $response = \Mockery::mock(ResponseInterface::class);
        $response->allows([
            'getBody' => '{"access_token":"mock_access_token", "scope":"user.read", "token_type":"bearer"}',
            'getHeader' => ['content-type' => 'json'],
            'getStatusCode' => 200,
        ]);

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData(): void
    {
        $userId = random_int(1000, 9999);
        $username = uniqid();
        $email = uniqid();
        $createdAt = new \DateTime(sprintf('-%d days', random_int(100, 9999)));
        $createdAt->setTime(
            $createdAt->format('H'),
            $createdAt->format('i'),
            $createdAt->format('s')
        );
        $credits = random_int(0, 1000);
        $links = [
            'self' => sprintf('https://api.cloudconvert.com/v2/users/%d', $userId),
        ];

        $postResponse = \Mockery::mock(ResponseInterface::class);
        $postResponse->allows([
            'getBody' => http_build_query([
                'access_token' => 'mock_access_token',
                'expires' => 3600,
                'refresh_token' => 'mock_refresh_token',
            ]),
            'getHeader' => ['content-type' => 'application/x-www-form-urlencoded'],
            'getStatusCode' => 200,
        ]);

        $userResponse = \Mockery::mock(ResponseInterface::class);
        $userResponse->allows([
            'getBody' => json_encode([
                'data' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'created_at' => $createdAt->format('c'),
                    'credits' => $credits,
                    'links' => $links,
                ],
            ]),
            'getHeader' => ['content-type' => 'json'],
            'getStatusCode' => 200,
        ]);

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        /** @var CloudConvertResourceOwner $user */
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['id']);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['username']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['email']);
        $this->assertEquals($createdAt, $user->getCreatedAt());
        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $user->toArray()['created_at']);
        $this->assertEquals($credits, $user->getCredits());
        $this->assertEquals($credits, $user->toArray()['credits']);
        $this->assertEquals($links, $user->getLinks());
        $this->assertEquals($links, $user->toArray()['links']);
        $this->assertStringContainsString($userId, $user->getLinks()['self']);
    }

    public function testExceptionThrownWhenErrorObjectReceived(): void
    {
        $status = random_int(400, 600);
        $postResponse = \Mockery::mock(ResponseInterface::class);
        $postResponse->allows([
            'getBody' => json_encode([
                'message' => 'The given data was invalid.',
                'code' => 'INVALID_DATA',
                'errors' => [
                    'tasks' => [
                        'The tasks field is required.'
                    ],
                ],
            ]),
            'getHeader' => ['content-type' => 'json'],
            'getStatusCode' => $status,
        ]);

        $client = \Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
