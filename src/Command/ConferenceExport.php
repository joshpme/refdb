<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Service\AuthorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Exception;
use MongoDB\Client;
use MongoDB\Driver\ServerApi;

/**
 * Import conferences via command line
 *
 * Class ImportCommand
 * @package App\Command
 */
class ConferenceExport extends Command
{
    private $manager;
    private $twig;
    private $searchDb;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:conference-synonyms';

    public function __construct(EntityManagerInterface $manager, Environment $twig, string $searchDb)
    {
        $this->twig = $twig;
        $this->manager = $manager;
        $this->searchDb = $searchDb;
        parent::__construct();
    }


    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uri = $this->searchDb;
        $apiVersion = new ServerApi(ServerApi::V1);
        $client = new \MongoDB\Client($uri, [], ['serverApi' => $apiVersion]);
        try {
            // Send a ping to confirm a successful connection
            $client->selectDatabase('search')->command(['ping' => 1]);
            echo "Pinged your deployment. You successfully connected to MongoDB!\n";
        } catch (Exception $e) {
            printf($e->getMessage());
        }

        $collection = $client->selectCollection("search", "synonyms");

        $conferences = $this->manager->getRepository(Conference::class)->findAll();

        /** @var Conference $conference */
        foreach ($conferences as $conference) {
            $confSyns = [
                $conference->getCode(),
            ];

            $short = str_replace("'", "", $conference->getCode());
            if ($short !== $conference->getCode()) {
                $confSyns[] = $short;
            }

            if ($conference->getDoiCode() !== null && $conference->getDoiCode() !== $conference->getCode()) {
                $confSyns[] = $conference->getDoiCode();
            }

            if (count($confSyns) > 1) {
                $collection->insertOne([
                    "mappingType" => "equivalent",
                    "synonyms" => $confSyns
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
