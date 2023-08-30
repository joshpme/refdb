<?php

namespace App\Enum;

enum FormatType: string {
    case Text = 'text';
    case BibTex = 'bibtex';
    case BibItem = 'bibitem';
    case Word = 'word';
}