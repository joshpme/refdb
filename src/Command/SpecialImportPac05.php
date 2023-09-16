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
class SpecialImportPac05 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-pac-05';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = "https://accelconf.web.cern.ch/p05/HTML/";
        $sessions = file($baseUrl . "SESS1.HTML");
        $urls = [];
        foreach ($sessions as $session) {
            if (preg_match("/<a href=\"([^\"]+)\"/u", $session, $matches)) {
                $urls[] = $baseUrl . $matches[1];
            }
        }
        $papers = [];
        foreach ($urls as $url) {
            $contents = file_get_contents(trim($url));
            preg_match_all('/>([A-Z]+[0-9]+)<\/a><\/td>\s+\n\s+<td class=\"paptitle\">(.*)\r\n\s+<\/td>\r\n.*>(.*)<\/a>/', $contents, $mainMatches);

            for ($i = 0; $i < count($mainMatches[0]); $i++) {
                $title = strip_tags($mainMatches[2][$i]);
                $papers[$mainMatches[1][$i]] = [
                    "title" => trim($title),
                    "position" => $mainMatches[3][$i],
                    "published" => $mainMatches[3][$i] !== ""
                ];
            }
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
                    if ($pn['published'] && $pn['position'] > $paper['position']) {
                        $nextPn = $pn['position'] - 1;
                        break;
                    }
                }
                if ($nextPn == 0) {
                    $nextPn = 4337;
                }
            }
            $positions[$code] = [ ...$paper, "position" => $paper['position'] . "-" . $nextPn];
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'05']);

        /** @var Reference $reference */
        foreach ($conference->getReferences() as $reference) {
            if (isset($positions[$reference->getPaperId()])) {
                $paper = $positions[$reference->getPaperId()];
                if ($paper['published']) {
                    $reference->setPosition($paper['position']);
                }
                $reference->setInProc($paper['published']);
                $reference->setCache($reference->__toString());
            } else {
                dump("Missing " . $reference->getPaperId());
            }
        }

        $this->manager->flush();
        return Command::SUCCESS;
    }
}
