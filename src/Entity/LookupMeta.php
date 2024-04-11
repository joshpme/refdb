<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class LookupMeta
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
     * @ORM\Column(name="item_type", type="string", length=255)
     */
    protected string $type;

    /**
     * @ORM\Column(type="string", nullable=true, length=1000)
     */
    protected ?string $journalName = null;

    public function getDoi(): string
    {
        return $this->doi;
    }

    public function setDoi(string $doi): void
    {
        $this->doi = $doi;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getJournalName(): ?string
    {
        return $this->journalName;
    }

    public function setJournalName(?string $journalName): void
    {
        $this->journalName = $journalName;
    }

}