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
class SpecialImport95 extends Command
{
    private EntityManagerInterface $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-95';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = file_get_contents("src/DataFixtures/Import/pac95.txt");
        preg_match_all("/([0-9]+)\s+[.]{5,}\s+([A-Z]+[0-9]+)/u", $data, $matches);
        $papers = [];
        $totalPapers = count($matches[0]);
        for($i = 0; $i < $totalPapers; $i++) {
            $startPage = $matches[1][$i];
            $paperCode = $matches[2][$i];
            if ($i < $totalPapers - 1) {
                $endPage = $matches[1][$i + 1] - 1;
            } else {
                $endPage = (int)($matches[1][$i]) + 3;
            }
            $papers[$paperCode] = $startPage . "-" . $endPage;
        }
        /**
         * @var Conference $conference
         */

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'95']);

        $references = $conference->getReferences();

        $changes = 0;
        /** @var Reference $reference */
        foreach ($references as $reference) {
            $code = $reference->getPaperId();
            if (isset($papers[$code])) {
                $reference->setPosition($papers[$code]);
                $reference->setCache($reference->__toString());
                $changes++;
            }

            if ($changes % 100 == 0) {
                $output->writeln("Updating references. Up to: " . $changes);
                $this->manager->flush();
            }
        }

        return Command::SUCCESS;
    }
}
