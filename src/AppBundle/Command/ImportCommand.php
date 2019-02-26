<?php

namespace AppBundle\Command;

use AppBundle\Entity\Conference;
use AppBundle\Service\ImportService;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import conferences via command line
 *
 * Class ImportCommand
 * @package AppBundle\Command
 */
class ImportCommand extends Command
{
    private $manager;
    private $importService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:import-conference';

    public function __construct(ObjectManager $manager, ImportService $importService)
    {
        $this->manager = $manager;
        $this->importService = $importService;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->addArgument("conf")
            ->addArgument("filename");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getArgument('conf');
        $filename = $input->getArgument('filename');

        $conference = $this->manager
            ->getRepository(Conference::class)
            ->findOneBy(["code" => $conf]);

        if ($conference === null) {
            $output->writeln("Could not find conference with Code: " . $conf);
            exit();
        } else {
            $output->writeln("Importing data into " . $conference->getName());
        }

        $this->importService->import($filename, $conference);
    }
}