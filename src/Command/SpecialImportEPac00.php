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
class SpecialImportEPac00 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-epac-00';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url= "https://accelconf.web.cern.ch/e00/TOC.html";
        $papers = [];
        $content = file_get_contents($url);

        /**

        <td width="95%"><font face="Arial, Helvetica, sans-serif"><a HREF="PAPERS/MOXE01.pdf"><b>The
        Importance of Particle Accelerators</b></a><br>
        U.&nbsp;AMALDI, TERA Foundation, Novara&nbsp;(MOXE01) </font></td>
        <td width="5%" align="right"><font face="Arial, Helvetica, sans-serif"><B>3</B></font></td>
         */
        if (preg_match_all("/\(([A-Z0-9]+)\)\s+<\/font>.*\n.*<B>([0-9]+)<\/B>/", $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $papers[$matches[1][$i]] = $matches[2][$i];
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
                $nextPn = 2620;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'EPAC\'00']);

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
