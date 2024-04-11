<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class Lookup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $doi;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    protected string $reference;

    public function getDoi(): string
    {
        return $this->doi;
    }

    public function setDoi(string $doi): void
    {
        $this->doi = $doi;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }
}