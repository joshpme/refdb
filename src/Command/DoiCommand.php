<?php

namespace App\Command;

use App\Entity\Reference;
use App\Service\DoiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import conferences via command line
 *
 * Class ImportCommand
 * @package App\Command
 */
class DoiCommand extends Command
{
    private $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:doi-checker';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }


    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $references = $this->manager->getRepository(Reference::class)->findAll();

        $doiService = new DoiService();
        /** @var Reference $reference */
        foreach ($references as $reference) {
            if ($reference->getConference()->isPublished() && (($reference->getInProc() && $reference->getConference()->isUseDoi() && !$reference->isDoiVerified()) ||
                    ($reference->getCustomDoi() !== null && $reference->getCustomDoi() !== "" && !$reference->isDoiVerified()))) {
                $valid = $doiService->check($reference);
                if (!$valid) {
                    $output->writeln("Failed on " . $reference->getConference()->getCode() . " " . $reference->getPaperId());
                } else {
                    $reference->setDoiVerified(true);
                    $this->manager->flush();
                    $output->writeln("Found DOI for " . $reference->getConference()->getCode() . " " . $reference->getPaperId());
                }

            }
        }
        return Command::SUCCESS;
    }
}
