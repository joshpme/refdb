<?php

namespace App\Enum;

enum FormatType: string {
    case BibTex = 'bibtex';
    case BibItem = 'bibitem';
    case Word = 'word';
}