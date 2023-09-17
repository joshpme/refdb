<?php

namespace App\Command;

use App\Entity\Conference;
use App\Entity\Reference;
use App\Http\CsvResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class ReportCommand extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:report';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conferences = $this->manager->getRepository(Conference::class)->findAll();

        $report = [];
        foreach ($conferences as $conference) {

            /** @var Reference $reference */
            $bad = 0;
            $missingPageNumbers = "GOOD";
            foreach ($conference->getReferences() as $reference) {
                if ($reference->getInProc() && ($reference->getPosition() === null || $reference->getPosition() == "" || $reference->getPosition() == "99-98")) {
                    $bad++;
                    if ($bad > 20) {
                        $missingPageNumbers = "FAIL";
                        break;
                    }
                }
            }

            $bad = 0;
            $badAuthors = "GOOD";
            foreach ($conference->getReferences() as $reference) {
                if (count($reference->getAuthors()) == 0) {
                    $bad++;
                    if ($bad > 10) {
                        $badAuthors = "FAIL";
                        break;
                    }
                }
            }

            $badTitles = "GOOD";
            foreach ($conference->getReferences() as $reference) {
                if ($reference->hasTitleIssue()) {
                    $bad++;
                    if ($bad > 20) {
                        $badTitles = "FAIL";
                        break;
                    }
                }
            }


            $report[] = [
                "code" => $conference->getCode(),
                "numberOfPapers" => $conference->getReferences()->count(),
                "missingPageNumbers" => $missingPageNumbers,
                "badAuthors" => $badAuthors,
                "badTitles" => $badTitles,
                "score" => ($missingPageNumbers == "GOOD" && $badAuthors == "GOOD" && $badTitles == "GOOD") ? "PASS" : "FAIL"
            ];
        }

        // sort by score
        usort($report, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        $response = new CsvResponse($report);
        file_put_contents("report.csv", $response->getContent());

        return Command::SUCCESS;
    }
}
