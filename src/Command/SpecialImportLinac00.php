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
class SpecialImportLinac00 extends Command
{
    private EntityManagerInterface $manager;
    private PaperService $paperService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-linac-00';

    public function __construct(EntityManagerInterface $manager, PaperService $paperService)
    {
        $this->manager = $manager;
        $this->paperService = $paperService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1800);

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'LINAC\'00']);

        $data = file("src/DataFixtures/Import/linac00.txt");

        $i = 1;
        foreach ($data as $index => $code) {
            $code = trim($code);
            if (empty($code)) {
                continue;
            }
            $paper = $this->manager->getRepository(Reference::class)->findOneBy(['conference' => $conference, 'paperId' => $code]);
            if ($paper === null) {
                $output->writeln("Code not found: " . $code);
            } else {
                $url = str_replace("https://jacow.org/", "http://accelconf.web.cern.ch/", $paper->getPaperUrl());
                $pdf = file_get_contents($url);
                $number = preg_match_all("/\/Page\W/", $pdf, $dummy);
                if ($number > 5) {
                    $output->writeln("Warning: " . $number . " pages for " . $paper->getPaperId());
                }
                $end = $i + $number - 1;
                if ($paper->getPaperId() == "TUB16") {
                    $number = 3;
                }
                $paper->setPosition("$i-$end");
                $i += $number;
                $paper->setCache($paper->__toString());
                $output->writeln("Updated " . $paper->getPaperId() . " to " . $paper->getPosition());
                $this->manager->flush();
            }
        }
        return Command::SUCCESS;
    }
}
