<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private Form $form;

    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Question $question;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stringValue = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $intValue = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $boolValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setStringValue(?string $stringValue): void
    {
        $this->stringValue = $stringValue;
    }

    public function getIntValue(): ?int
    {
        return $this->intValue;
    }

    public function setIntValue(?int $intValue): void
    {
        $this->intValue = $intValue;
    }

    public function getBoolValue(): ?bool
    {
        return $this->boolValue;
    }

    public function setBoolValue(?bool $boolValue): void
    {
        $this->boolValue = $boolValue;
    }

    public function getValue(): string|int|bool|null
    {
        return $this->stringValue ?? $this->intValue ?? $this->boolValue ?? null;
    }


}
