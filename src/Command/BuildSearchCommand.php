<?php

namespace App\Command;

use App\Entity\Reference;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class BuildSearchCommand extends Command
{
    private EntityManagerInterface $manager;
    private SearchService $searchService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:build-search';

    public function __construct(EntityManagerInterface $manager, SearchService $searchService)
    {
        $this->manager = $manager;
        $this->searchService = $searchService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 60 * 60 * 2);
        $references = $this->manager->getRepository(Reference::class)->findBy([],["paperId" => "ASC"]);

        $i = 0;
        foreach ($references as $reference) {
            $i++;
            $this->searchService->insertOrUpdate($reference);
            $output->writeln("Updated " . $i . " references of " . count($references));
        }
        return Command::SUCCESS;
    }
}
