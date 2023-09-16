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
class SpecialImportEPac98 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-epac-98';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = [];
        $urls[] = "catinv.html";
        $urls[] = "catcontr.html";
        $urls[] = "atitles.html";
        $urls[] = "utitles.html";
        $urls[] = "dtitles.html";
        $urls[] = "ttitles.html";
        $baseUrl = "http://accelconf.web.cern.ch/e98/";
        $papers = [];
        foreach ($urls as $url) {

            $content = file_get_contents($baseUrl . $url);

            /**
             *  (<A HREF="ABSTRACTS/AND1034.pdf">Abstract</A>)
            <DIV ALIGN=RIGHT> <B>1385</B></DIV>
             */
            if (preg_match_all("/\/([A-Z0-9]+)\.PDF.*\n.*\n.*\n.*<B>([0-9]+)<\/B>/", $content, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $papers[$matches[1][$i]] = $matches[2][$i];
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
                $nextPn = 2460 + 3;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'EPAC\'98']);

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
