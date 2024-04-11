<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class Journal
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="short_canonical", type="string", length=255)
     */
    protected ?string $shortCanonical = null;

    /**
     * @ORM\Column(name="name_short", type="string", length=255)
     */
    protected string $short;

    /**
     * @ORM\Column(name="name_long", type="string", length=400)
     */
    protected string $long;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getShort(): string
    {
        return $this->short;
    }

    public function setShort(string $short): void
    {
        $this->short = $short;
    }

    public function getLong(): string
    {
        return $this->long;
    }

    public function setLong(string $long): void
    {
        $this->long = $long;
    }

    public function getShortCanonical(): ?string
    {
        return $this->shortCanonical;
    }

    public function setShortCanonical(?string $shortCanonical): void
    {
        $this->shortCanonical = $shortCanonical;
    }
}