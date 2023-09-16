<?php

namespace App\Command;

use App\Entity\Conference;
use App\Entity\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportPac99 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-pac-99';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = "http://accelconf.web.cern.ch/p99/";
        $urls = ["vol1.htm", "vol2.htm", "vol3.htm", "vol4.htm", "vol5.htm"];

        $papers = [];
        foreach ($urls as $url) {
            $content = file_get_contents($baseUrl . $url);

            /**
            <A HREF="PAPERS/THP11.PDF">
            <STRONG>Commissioning Status of the KEKB Linac</STRONG>
            </A>
            --
            <em>Y.Ogawa, Linac Commissioning Group, KEK, Tsukuba, Ibaraki, Japan</em></TD>
            <TD ALIGN="right" VALIGN="top">
            2984</TD>
             */
            preg_match_all("/\/([A-Z0-9]+)\.PDF/x", $content, $codeMatches);

            preg_match_all("/<TD\s+ALIGN=\"right\"\s+VALIGN=\"top\">\s+([0-9]+)/x", $content, $posMatches);

            if (count($codeMatches[1]) != count($posMatches[1])) {
                $output->writeln("Error: " . $url);
                exit();
            }
            foreach ($codeMatches[1] as $i => $code) {
                $papers[$code] = $posMatches[1][$i];
            }
        }

        asort($papers);
        
        $positions = [];
        foreach ($papers as $code => $paper) {
            $nextPn = 0;
            // find next higher pagenumber
            foreach ($papers as $pn) {
                if (filter_var($pn, FILTER_VALIDATE_INT) && $pn > $paper) {
                    $nextPn = $pn - 1;
                    break;
                }
            }
            if ($nextPn == 0) {
                $nextPn = 3778;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'99']);

        $references = $conference->getReferences();

        $changes = 0;
        /** @var Reference $reference */
        foreach ($references as $reference) {
            $code = $reference->getPaperId();
            if (isset($positions[$code])) {
                $reference->setPosition($positions[$code]);
                $reference->setCache($reference->__toString());
                $changes++;
            } else {
                $output->writeln("No position for: " . $code);
            }

            if ($changes % 100 == 0) {
                $output->writeln("Updating references. Up to: " . $changes);
                $this->manager->flush();
            }
        }

        $this->manager->flush();

        return Command::SUCCESS;
    }
}
