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
            new TwigFilter('ordinal', [$this, 'ordinal']),
        ];
    }

    public function ordinal($number)
    {
        if ($number === null || $number === '') {
            return $number;
        }

        $number = (int) $number;
        $suffix = 'th';
        if (!in_array($number % 100, [11, 12, 13], true)) {
            $suffix = match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $number . $suffix;
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
        $text = strip_tags($text, "<em><sup><sub><br><a>");
        $text = str_replace(" et al.", " <em>et al.</em>", $text);
        return $text;
    }
}
