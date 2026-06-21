<?php

namespace App\Command;

use App\Entity\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Regenerates all reference cache
 *
 * Class ImportCommand
 * @package App\Command
 */
#[AsCommand(name: 'app:reference-cache')]
class ReferenceCacheCommand extends Command
{
    private $manager;


    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        $manager = $this->manager;
        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->getQuery()
            ->getResult();

        $cleaned = 0;
        foreach ($results as $result) {
            if ($result->getPaperId() == "") {
                $result->setPaperId(null);
            }
            if ($result->getCache() !== $result->__toString()) {
                $result->setCache($result->__toString());
                $cleaned++;
            }
        }

        $output->writeln("Regenerating " . $cleaned . " references");
        $manager->flush();

        return Command::SUCCESS;
    }
}
