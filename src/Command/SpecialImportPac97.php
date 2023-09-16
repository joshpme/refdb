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
class SpecialImportPac97 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-pac-97';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = "https://accelconf.web.cern.ch/pac97/papers/";
        $urls = ["vol1.html", "vol2.html", "vol3.html"];

        $papers = [];
        foreach ($urls as $url) {
            $lines = file($baseUrl . $url);
            // <tr><td><b>p. 20</b></td><td><b><a href="pdf/6C003.PDF">The National Spallation Neutron Source (NSNS) Project</a></b></td><td><b>6C003</b></td></tr>
            foreach ($lines as $line) {
                if (preg_match("/<b>p. ([0-9]+)<\/b>.*<b>([A-Z0-9]+)<\/b>/", $line, $matches)) {
                    $papers[$matches[2]] = $matches[1];
                }
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
                $nextPn = 3874;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'97']);

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
