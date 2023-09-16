<?php

namespace App\Command;

use App\Entity\Conference;
use App\Entity\Reference;
use App\Service\PaperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportLinac10 extends Command
{
    private EntityManagerInterface $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-linac-10';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1800);

        $data = file("src/DataFixtures/Import/linac10.txt");

        $papers = [];
        foreach ($data as $url) {
            $contents = file_get_contents(trim($url));

            preg_match_all('/>([A-Z]+[0-9]+)<\/a>.*\n.*\n.*\n.*>([0-9]+)<\/a>/', $contents, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $papers[$matches[1][$i]] = $matches[2][$i];
            }
        }


        asort($papers);


        $positions = [];
        foreach ($papers as $code => $pageNumber) {
            $nextPn = 0;
            // find next higher pagenumber
            foreach ($papers as $pn) {
                if ($pn > $pageNumber) {
                    $nextPn = $pn - 1;
                    break;
                }
            }
            if ($nextPn == 0) {
                $nextPn = 1052;
            }
            $positions[$code] = $pageNumber . "-" . $nextPn;
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'LINAC\'10']);

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
                $output->writeln("Code not found: " . $code);
            }

            if ($changes % 100 == 0) {
                $output->writeln("Updating references. Up to: " . $changes);
                $this->manager->flush();
            }
        }

        dump($papers);

        return Command::SUCCESS;
    }
}
