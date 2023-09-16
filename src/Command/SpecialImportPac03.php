<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportPac03 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-pac-03';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = "http://accelconf.web.cern.ch/p03/HTML/";
        $sessions = file($baseUrl . "TOC1.HTM");
        $urls = [];

        foreach ($sessions as $session) {
            if (preg_match("/<a href=\"([A-Z.^\"]+)\"/iu", $session, $matches)) {
                $urls[] = $baseUrl . $matches[1];
            }
        }

        //$urls = ["http://accelconf.web.cern.ch/p03/HTML/ROAB.HTM"];
        $papers = [];
        foreach ($urls as $url) {
            $contents = file_get_contents(trim($url));
            preg_match_all('/>([A-Z]+[0-9]+)(<\/A>)?<\/TD>.*(\r\n)+<TD>(.*)<\/TD>(\r\n)+.*>(.*)<\/TD>/i', $contents, $mainMatches);

            for ($i = 0; $i < count($mainMatches[0]); $i++) {
                $title = strip_tags($mainMatches[4][$i]);
                $papers[$mainMatches[1][$i]] = [
                    "title" => trim($title),
                    "position" => $mainMatches[6][$i],
                    "published" => $mainMatches[6][$i] != "*",
                ];
            }
            $output->writeln("Processed " . $url);
        }

        uasort($papers, function ($a, $b) {
            return $a["position"] <=> $b["position"];
        });

        $positions = [];
        foreach ($papers as $code => $paper) {
            $nextPn = 0;
            if ($paper['published']) {
                // find next higher pagenumber
                foreach ($papers as $pn) {
                    if ($pn['published'] && filter_var($pn['position'], FILTER_VALIDATE_INT) && $pn['position'] > $paper['position']) {
                        $nextPn = $pn['position'] - 1;
                        break;
                    }
                }
                if ($nextPn == 0) {
                    $nextPn = 3571;
                }
            }
            $positions[$code] = [ ...$paper, "position" => $paper['position'] . "-" . $nextPn];
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'03']);

        /** @var Reference $reference */
        foreach ($conference->getReferences() as $reference) {
            if (isset($positions[$reference->getPaperId()])) {
                $paper = $positions[$reference->getPaperId()];
                $reference->setTitle($paper['title']);
                $reference->setInProc($paper['published']);
                if ($paper['published']) {
                    $reference->setPosition($paper['position']);
                }
                $reference->setCache($reference->__toString());
            } else {
                $output->writeln("Missing " . $reference->getPaperId());
            }
        }

        $this->manager->flush();
        return Command::SUCCESS;
    }
}
