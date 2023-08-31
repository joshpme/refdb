<?php

namespace App\Command;

use App\Entity\Reference;
use App\Service\PaperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Regenerates all reference cache
 *
 * Class ImportCommand
 * @package App\Command
 */
class PaperUrlsCommand extends Command
{
    private $manager;
    private PaperService $paperService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:paper-urls';

    public function __construct(EntityManagerInterface $manager, PaperService $paperService)
    {
        $this->paperService = $paperService;
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        $manager = $this->manager;
        /** @var Reference[] $results */
        $references = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where('r.paperUrl IS NOT NULL')
            ->getQuery()
            ->getResult();

        $updated = 0;
        foreach ($references as $reference) {
            if ($this->paperService->check($reference)) {
                $updated++;
            }
        }

        $output->writeln("Paper URLs updated: (" . $updated . ")");
        $manager->flush();

        return Command::SUCCESS;
    }
}
