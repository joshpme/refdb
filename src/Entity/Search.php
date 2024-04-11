<?php

namespace App\Entity;

use App\Enum\FormatType;

class Search
{
    private ?string $query = null;

    private ?FormatType $formatType = FormatType::Text;

    private ?bool $checkExternal = false;

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): void
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

    public function getCheckExternal(): ?bool {
        return $this->checkExternal;
    }

    public function setCheckExternal(?bool $checkExternal): void {
        $this->checkExternal = $checkExternal;
    }

}
