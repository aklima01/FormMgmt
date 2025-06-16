<?php

namespace App\Entity;

use App\Entity\Topic;
use App\Entity\User;
use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;



#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\Table(name: 'templates')]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Topic::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Topic $topic;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'template_access_users')]
    private Collection $accessUsers;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;



    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;


    public function __construct()
    {
        $this->accessUsers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // -- Getters & Setters --

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTopic(): Topic
    {
        return $this->topic;
    }

    public function setTopic(Topic $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getAccessUsers(): Collection
    {
        return $this->accessUsers;
    }

    public function addAccessUser(User $user): self
    {
        if (!$this->accessUsers->contains($user)) {
            $this->accessUsers->add($user);
        }
        return $this;
    }

    public function removeAccessUser(User $user): self
    {
        $this->accessUsers->removeElement($user);
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }


}
