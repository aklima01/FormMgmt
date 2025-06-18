<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->questions = new ArrayCollection();
    }


    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // Getter and setter
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }



    #[ORM\ManyToMany(targetEntity: Tag::class, cascade: ["persist"])]
    #[ORM\JoinTable(name: "template_tag")]
    private Collection $tags;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'template_user_access')]
    private Collection $users;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $access = null;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $questions;


    #[ORM\ManyToOne(targetEntity: Topic::class)]
    #[ORM\JoinColumn(name: 'topic_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Topic $topic = null;

    // Getter
    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    // Setter
    public function setTopic(?Topic $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function setQuestions(Collection $questions): void
    {
        $this->questions = $questions;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setUsers(Collection $users): void
    {
        $this->users = $users;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function setAccess(string $access): void
    {
        $this->access = $access;
    }



    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }



    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }



    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }



    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }


    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }
}
