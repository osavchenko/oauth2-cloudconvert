<?php

declare(strict_types=1);

namespace Osavchenko\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class CloudConvertResourceOwner implements ResourceOwnerInterface
{
    private int $id;
    private string $username;
    private string $email;
    private int $credits;
    private array $links;
    private \DateTime $createdAt;

    public function __construct(array $response)
    {
        $data = $response['data'];

        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->credits = $data['credits'];
        $this->links = $data['links'];
        $this->createdAt = new \DateTime($data['created_at']);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    /**
     * @return string[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'credits' => $this->credits,
            'links' => $this->links,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
