<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\CheeseListingRepository;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: CheeseListingRepository::class)]
#[ApiResource(
    collectionOperations: ['get', 'post'],
    itemOperations: ['get' => [
        'normalization_context' => [
            'groups' => [
                self::GROUPS_CHEESE_LISTING_READ,
                self::GROUPS_CHEESE_LISTING_ITEM_GET,
            ],
            'swagger_definition_name' => 'read-item',
        ],
    ], 'put', 'delete', 'patch'],
    shortName: 'cheeses',
    attributes: [
        'pagination_items_per_page' => 10,
        'formats' => ['jsonld', 'json', 'jsonld', 'html', 'csv' => 'text/csv'],
    ],
    denormalizationContext: [
        'groups' => [self::GROUPS_CHEESE_LISTING_WRITE],
        'swagger_definition_name' => 'write',
    ],
    normalizationContext: [
        'groups' => [self::GROUPS_CHEESE_LISTING_READ],
        'swagger_definition_name' => 'read',
    ]
)]
#[ApiFilter(BooleanFilter::class, properties: ['isPublished'])]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => SearchFilterInterface::STRATEGY_PARTIAL,
    'description' => SearchFilterInterface::STRATEGY_PARTIAL,
    'owner' => SearchFilterInterface::STRATEGY_EXACT,
])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(PropertyFilter::class)]
class CheeseListing
{
    public const GROUPS_CHEESE_LISTING_WRITE = 'cheese_listing:write';
    public const GROUPS_CHEESE_LISTING_READ = 'cheese_listing:read';
    public const GROUPS_CHEESE_LISTING_ITEM_GET = 'cheese_listing:item:get';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[UuidConstraint]
    private ?Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[ApiProperty(description: 'la description coute de mon fromage')]
    #[Groups([self::GROUPS_CHEESE_LISTING_READ, self::GROUPS_CHEESE_LISTING_WRITE, User::GROUPS_USER_READ, User::GROUPS_USER_WRITE])]
    #[NotBlank]
    #[Length(min: 5, max: 50, maxMessage: 'Décrivé votre formage en 50 caractères ou moins')]
    private ?string $title;

    #[ORM\Column(type: 'text')]
    #[ApiProperty(description: 'La description du fromage')]
    #[Groups([self::GROUPS_CHEESE_LISTING_READ])]
    #[NotBlank]
    private ?string $description;

    #[ORM\Column(type: 'integer')]
    #[ApiProperty(description: 'Le prix du fromage')]
    #[Groups([self::GROUPS_CHEESE_LISTING_READ, self::GROUPS_CHEESE_LISTING_WRITE, User::GROUPS_USER_READ, User::GROUPS_USER_WRITE])]
    #[NotBlank]
    #[Type('int')]
    private ?int $price;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isPublished = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cheeseListings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUPS_CHEESE_LISTING_READ, self::GROUPS_CHEESE_LISTING_WRITE])]
    #[Valid]
    private ?User $owner;

    public function __construct(string $title = null)
    {
        $this->title = $title;
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups([self::GROUPS_CHEESE_LISTING_READ])]
    public function setShortDescription(): ?string
    {
        if (strlen($this->description) < 40) {
            return $this->description;
        }

        return substr($this->description, 0, 40).'...';
    }

    #[Groups([self::GROUPS_CHEESE_LISTING_WRITE])]
    #[SerializedName('description')]
    #[ApiProperty(description: 'La description du fromage en tant que texte brute')]
    public function setTextDescription(string $description): self
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups([self::GROUPS_CHEESE_LISTING_READ])]
    #[ApiProperty(description: "Depuis combien de temps en texte le fromage a-t'il été ajouté")]
    public function getCreatedAtAgo(): string
    {
        return (Carbon::instance($this->getCreatedAt()))->locale('fr_FR')->diffForHumans();
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
