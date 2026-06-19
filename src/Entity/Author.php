<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\AuthorRepository::class)]
#[ORM\Table(name: 'author')]
#[ORM\Index(name: 'author_search_idx', columns: ['name'])]
class Author implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    // The authors name
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * Their associated references
     * @var ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Reference::class, inversedBy: 'authors', cascade: ['persist', 'remove'])]
    private $references;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Author
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function addReference(Reference $reference) {
        $this->references->add($reference);
    }
    /**
     * @return ArrayCollection
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @param ArrayCollection $references
     */
    public function setReferences($references)
    {
        $this->references = $references;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    /**
     * Remove reference
     *
     * @param Reference $reference
     */
    public function removeReference(Reference $reference)
    {
        $this->references->removeElement($reference);
    }

    public function jsonSerialize(): array
    {
        return [
            "id"=>$this->getId(),
            "name"=>$this->getName()
        ];
    }
}
