<?php

namespace App\Command;

use App\Entity\Conference;
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

    protected function configure()
    {
        $this
            ->addArgument("conf");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 60 * 60 * 2);
        $conf = $input->getArgument('conf');
        $conference = $this->manager
            ->getRepository(Conference::class)
            ->findOneBy(["code" => $conf]);
        if ($conference === null) {
            $output->writeln("Could not find conference with Code: " . $conf);
            exit();
        } else {
            $output->writeln("Building search index for " . $conference->getName());
        }
        $references = $this->manager->getRepository(Reference::class)->findBy(["conference"=>$conference],["paperId" => "ASC"]);
        $i = 0;
        foreach ($references as $reference) {
            $i++;
            $this->searchService->insertOrUpdate($reference);
            $output->writeln("Updated " . $i . " references of " . count($references));
        }
        return Command::SUCCESS;
    }
}
