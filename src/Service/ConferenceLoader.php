<?php

namespace App\Service;

use App\Entity\Conference;

class ConferenceLoader
{
    public function load(Conference $conference, string $source): ?string {
        $safeLocation = "https://jacow.org/";
        if (!str_starts_with($source, $safeLocation)) {
            return "Source not from a safe location";
        }
        $contents = file_get_contents($source);
        if ($contents === false) {
            return "Could not fetch data";
        }
        $values = json_decode($contents, true);
        if ($values === null) {
            return "Not valid json";
        }
        if (isset($values['date_from'])) {
            $conference->setConferenceStart(new \DateTime($values['date_from']));
        }
        if (isset($values['date_to'])) {
            $conference->setConferenceEnd(new \DateTime($values['date_to']));
        }
        if (isset($values['dates_my'])) {
            $conference->setYear($values['dates_my']);
        }
        if (isset($values['fullname'])) {
            $conference->setName($values['fullname']);
        }
        if (isset($values['issn'])) {
            $conference->setIssn($values['issn']);
        }
        if (isset($values['location'])) {
            $conference->setLocation($values['location']);
        }
        if (isset($values['name'])) {
            $conference->setCode($values['name']);
        }
        if (isset($values['serie'])) {
            $conference->setSeries($values['serie']);
        }
        if (isset($values['serie_number'])) {
            $conference->setSeriesNumber($values['serie_number']);
        }
        if (isset($values['url'])) {
            $conference->setBaseUrl($values['url']);
        }
        return null;
    }
}