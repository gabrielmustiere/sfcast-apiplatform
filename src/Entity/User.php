<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Uuid as UuidAlias;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email', 'username'])]
#[ApiResource(
    shortName: 'users',
    denormalizationContext: [
        'groups' => self::GROUPS_USER_WRITE,
        'swagger_definition_name' => 'write',
    ],
    normalizationContext: [
        'groups' => self::GROUPS_USER_READ,
        'swagger_definition_name' => 'read',
    ]
)]
#[ApiFilter(PropertyFilter::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const GROUPS_USER_WRITE = 'user:write';
    public const GROUPS_USER_READ = 'user:read';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[UuidAlias]
    private ?Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups([self::GROUPS_USER_READ, self::GROUPS_USER_WRITE])]
    private ?string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups([self::GROUPS_USER_WRITE])]
    #[ApiProperty(description: 'Le password hashé')]
    private string $password;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups([self::GROUPS_USER_READ, self::GROUPS_USER_WRITE, CheeseListing::GROUPS_CHEESE_LISTING_ITEM_GET, CheeseListing::GROUPS_CHEESE_LISTING_WRITE])]
    private string $username;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: CheeseListing::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups([self::GROUPS_USER_READ, self::GROUPS_USER_WRITE])]
    private Collection $cheeseListings;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->cheeseListings = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials()
    {
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getCheeseListings(): Collection
    {
        return $this->cheeseListings;
    }

    public function addCheeseListing(CheeseListing $cheeseListing): self
    {
        if (!$this->cheeseListings->contains($cheeseListing)) {
            $this->cheeseListings[] = $cheeseListing;
            $cheeseListing->setOwner($this);
        }

        return $this;
    }

    public function removeCheeseListing(CheeseListing $cheeseListing): self
    {
        if ($this->cheeseListings->removeElement($cheeseListing)) {
            if ($cheeseListing->getOwner() === $this) {
                $cheeseListing->setOwner(null);
            }
        }

        return $this;
    }
}
