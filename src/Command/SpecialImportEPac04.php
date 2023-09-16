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
class SpecialImportEPac04 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-epac-04';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url= "http://accelconf.web.cern.ch/e04/HTML/CLASS1.HTML";
        $papers = [];
        $content = file_get_contents($url);
        preg_match_all('/href="(.*)\.HTML">/i', $content, $matches);
        $urls = $matches[1];

        foreach ($urls as $url) {
            $output->writeln("Processing: " . $url);
            $contents = file_get_contents("http://accelconf.web.cern.ch/e04/html/" . $url . ".HTML");

            /** <a href="../PAPERS/MOPLT147.PDF" target="_pdf">884</a></td> */
            preg_match_all('/\/([a-zA-Z0-9]+)\.PDF" target="_pdf">([0-9]+)<\/a>/', $contents, $matches);

            for ($i = 0; $i < count($matches[0]); $i++) {
                $papers[$matches[1][$i]] = $matches[2][$i];
            }

            $output->writeln("Total papers: " . count($papers));
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
                $nextPn = 2887;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'EPAC\'04']);

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
