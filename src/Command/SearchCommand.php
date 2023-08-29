<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Exception;
use MongoDB\Driver\ServerApi;

/**
 * Import conferences via command line
 *
 * Class ImportCommand
 * @package App\Command
 */
class SearchCommand extends Command
{
    private $manager;
    private $twig;
    private $searchDb;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:search-cache';

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

        $collection = $client->selectCollection("search", "search");

        $references = $this->manager->getRepository(Reference::class)->findAll();

        foreach ($references as $reference) {
            $authors = $reference->getAuthors();
            $lastNames = [];
            /** @var Author $author */
            foreach ($authors as $author) {
                $nameParts = explode(". ", $author->getName());
                $lastNames[] = end($nameParts);
            }

            $bibitem = $this->twig->render("reference/latex.html.twig", ["reference" => $reference]);
            $bibtex = $this->twig->render("reference/bibtex.html.twig", ["reference" => $reference]);
            $word = $this->twig->render("reference/word.html.twig", ["reference" => $reference]);

            $code = $reference->getConference()->getCode();

            $data = [
                "ref_id" => $reference->getId(),
                "title" => $reference->getTitle(),
                "paper_code" => $reference->getPaperId(),
                "conference_code" => $reference->getConference()->getCode(),
                "conference_name" => $reference->getConference()->getName(),
                "authors" => implode(" ", $lastNames),
                "bibitem" => $bibitem,
                "bibtex" => $bibtex,
                "word" => $word
            ];

            if (!empty($reference->getConference()->getYear())) {
                $data["date"] = $reference->getConference()->getYear();
            }

            if (!empty($reference->getConference()->getLocation())) {
                $data["location"] = $reference->getConference()->getLocation();
            }

            if (!empty($reference->doiOnly())) {
                $data["doi"] = $reference->doiOnly();
            }

            $result = $collection->findOne(["conference_code"=>$code, "paper_code"=>$reference->getPaperId()]);
            if ($result === null) {
                $collection->insertOne($data);
                $output->writeln($code . "/" . $reference->getPaperId());
            } else {
                $collection->updateOne(["_id" => $result->_id], ['$set' => $data]);
                $output->writeln($code . "/" . $reference->getPaperId() . " Updated");
            }
        }
        return Command::SUCCESS;
    }
}
