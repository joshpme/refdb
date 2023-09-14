<?php

namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private $twig;
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('latin', [$this, 'latinReplace'], ['is_safe' => ['html']]),
        ];
    }

    private function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    public function latinReplace($text)
    {
        $text = strip_tags($text, "<em><sup><sub><br>");
        $text = str_replace(" et al.", " <em>et al.</em>", $text);
        return $text;
    }
}
