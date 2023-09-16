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
class SpecialImportLinac02 extends Command
{
    private EntityManagerInterface $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-linac-02';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1800);

        $data = file("src/DataFixtures/Import/linac02.txt");

        $expecting = "papercode";
        $paperCode = null;
        $previousPaperCode = null;
        $paperTitle = null;
        $pageNumber = null;
        $papers = [];
        foreach ($data as $line) {
            if ($expecting == "papercode") {
                $isMatch = preg_match("/^([A-Z]+[0-9]+).+$/", $line, $matches);
                if ($isMatch) {
                    $previousPaperCode = $paperCode;
                    $paperCode = $matches[1];
                    $expecting = "title";
                }
            } elseif ($expecting == "title") {
                $paperTitle = trim($line);
                $expecting = "page";
            } elseif ($expecting == "page") {
                $isMatch = preg_match("/^[0-9]+$/", trim($line), $matches);
                $published = null;
                if ($isMatch) {
                    $previousPageNumber = $pageNumber;
                    $pageNumber = $matches[0];
                    $expecting = "authors";
                    $published = true;
                } else {
                    $authors = trim($line);
                    $published = false;
                    $expecting = "papercode";
                }
            } else {
                $authors = trim($line);
                $expecting = "papercode";
            }
            if ($expecting == "papercode") {
                $papers[$paperCode] = [
                    'title' => $paperTitle,
                    'authors' => $authors,
                    'position' => (int)$pageNumber,
                    'published' => $published,
                ];
            }
        }

        foreach ($papers as $code => &$paper) {
            if ($paper['published'] && filter_var($paper['position'], FILTER_VALIDATE_INT) === false) {
                continue;
            }
            foreach ($papers as $subpaper) {
                if ($subpaper['published'] && filter_var($subpaper['position'], FILTER_VALIDATE_INT) === false) {
                    continue;
                }
                if ($subpaper['published'] && $subpaper['position'] > $paper['position']) {
                    $paper['pageNumbers'] = (string)$paper['position'] . "-" . $subpaper['position'] - 1;
                    break;
                }
            }
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'LINAC\'02']);

        /** @var Reference $reference */
        foreach ($conference->getReferences() as $reference) {
            if (isset($papers[$reference->getPaperId()])) {
                $paper = $papers[$reference->getPaperId()];
                if ($paper['published']) {
                    if (!isset($paper['pageNumbers'])) {
                        $paper['pageNumbers'] = $paper['position'] . "-830";
                    }
                    $reference->setPosition($paper['pageNumbers']);
                }
                $reference->setInProc($paper['published']);
                $reference->setCache($reference->__toString());
            } else {
                $output->writeln("Missing " . $reference->getPaperId());
            }
        }

        $this->manager->flush();
        return Command::SUCCESS;
    }
}
