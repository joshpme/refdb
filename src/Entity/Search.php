<?php

namespace App\Entity;

use App\Enum\FormatType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Search
{
    private ?string $query = null;

    private ?FormatType $formatType = null;

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getFormatType(): ?FormatType
    {
        return $this->formatType;
    }

    public function setFormatType(?FormatType $formatType): void
    {
        $this->formatType = $formatType;
    }

}
