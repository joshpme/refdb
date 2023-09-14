<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * Feedback, for users to alert admins of incorrect references
 *
 * @ORM\Table(name="feedback")
 * @ORM\Entity(repositoryClass="App\Repository\FeedbackRepository")
 */
class Feedback
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    private ?string $feedback = null;

    /**
     * @Assert\Length(max=500)
     * @ORM\Column(name="title", type="string", length=500, nullable=true)
     */
    private ?string $title = null;

    /**
     * @Assert\Length(max=500)
     * @ORM\Column(name="author", type="string", length=500, nullable=true)
     */
    private ?string $author = null;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(name="position", type="string", length=255, nullable=true)
     */
    private ?string $position = null;

    /**
     * @Assert\Length(max=100)
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $customDoi = null;

    /**
     * @Assert\Email()
     * @Assert\Length(max=100)
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $resolved = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Reference", inversedBy="feedback")
     */
    private Reference $reference;

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): void
    {
        $this->resolved = $resolved;
    }

    public function getCustomDoi(): ?string
    {
        return $this->customDoi;
    }

    public function setCustomDoi(?string $customDoi): void
    {
        $this->customDoi = $customDoi;
    }

    public function getReference(): Reference
    {
        return $this->reference;
    }

    public function setReference(Reference $reference): void
    {
        $this->reference = $reference;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): void
    {
        $this->feedback = $feedback;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}

