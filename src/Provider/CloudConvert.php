<?php

declare(strict_types=1);

namespace Osavchenko\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Osavchenko\OAuth2\Client\Provider\Exception\CloudConvertIdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class CloudConvert extends AbstractProvider
{
    protected const BASE_URL = 'https://cloudconvert.com';
    protected const API_URL = 'https://api.cloudconvert.com';

    protected const SANDBOX_BASE_URL = 'https://sandbox.cloudconvert.com';
    protected const SANDBOX_API_URL = 'https://api.sandbox.cloudconvert.com';

    public const SCOPE_USER_READ = 'user.read';
    public const SCOPE_USER_WRITE = 'user.write';
    public const SCOPE_TASK_READ = 'task.read';
    public const SCOPE_TASK_WRITE = 'task.write';
    public const SCOPE_WEBHOOK_READ = 'webhook.read';
    public const SCOPE_WEBHOOK_WRITE = 'webhook.write';

    protected bool $isSandbox;

    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->isSandbox = $options['sandbox'] ?? false;

        parent::__construct($options, $collaborators);
    }


    public function getBaseAuthorizationUrl(): string
    {
        return sprintf('%s/%s', $this->isSandbox ? self::SANDBOX_BASE_URL : self::BASE_URL, 'oauth/authorize');
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return sprintf('%s/%s', $this->isSandbox ? self::SANDBOX_BASE_URL : self::BASE_URL, 'oauth/token');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return sprintf('%s/%s', $this->isSandbox ? self::SANDBOX_API_URL : self::API_URL, 'v2/users/me');
    }

    protected function getDefaultScopes(): array
    {
        return [self::SCOPE_USER_READ];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new CloudConvertIdentityProviderException(
                $data['message'] ?? $response->getReasonPhrase(),
                $response->getStatusCode(),
                (string) $response->getBody()
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): CloudConvertResourceOwner
    {
        return new CloudConvertResourceOwner($response);
    }

    protected function getAuthorizationHeaders($token = null): array
    {
        if ($token === null) {
            return [];
        }

        return [
            'Authorization' => sprintf('Bearer %s', (string) $token),
        ];
    }
}
