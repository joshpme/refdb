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
class SpecialImportLinac96 extends Command
{
    private EntityManagerInterface $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-linac-96';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = file("src/DataFixtures/Import/linac96.txt");

        foreach ($data as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $parts = explode(" ", $line);
            if (count($parts) > 1) {
                $code = $parts[0];
                $position = $parts[1];
                $papers[$code] = $position;
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
                $nextPn = 914;
            }
            $positions[$code] = $pageNumber . "-" . $nextPn;
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'LINAC\'96']);

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

        return Command::SUCCESS;
    }
}
