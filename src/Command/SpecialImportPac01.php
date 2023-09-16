<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Service\PaperService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportPac01 extends Command
{
    private EntityManagerInterface $manager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:special-import-pac-01';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1800);

        $data = file("src/DataFixtures/Import/pac01.txt");

        $papers = [];
        foreach ($data as $url) {
            $contents = file_get_contents(trim($url));
            preg_match_all('/>([A-Z]+[0-9]+)(<\/A>)?<\/TD>.*\r\n<TD>(.*)<\/TD>\r\n.*>(.*)<\/TD>/', $contents, $mainMatches);
            for ($i = 0; $i < count($mainMatches[0]); $i++) {

                $title = str_replace("(Invited)", "", $mainMatches[3][$i]);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/PM.GIF" width="12" height="14">', "±", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/PHIU.GIF" width="12" height="14">', "ɸ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/PHIU.GIF" width="11" height="13">', "ɸ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/PHIL.GIF" width="10" height="17">', "φ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/DELTAU.GIF" width="14" height="14">', "Δ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/DELTAL.GIF" width="8" height="15">', "δ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/NU.GIF" width="8" height="8">', "Neutrino", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/BETAL.GIF" width="11" height="16">', "β", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/GAMMAL.GIF" width="10" height="13">', "γ", $title);
                $title = str_replace('<IMG SRC="../GRAPHICS/CHARGIFS/REG.GIF" width="13" height="15">', "®", $title);

                $title = strip_tags($title);
                $papers[$mainMatches[1][$i]] = [
                    "title" => trim($title),
                    "position" => $mainMatches[4][$i],
                    "published" => $mainMatches[4][$i] !== "&nbsp;"
                ];
            }

            preg_match_all('/UL><LI><I>(.+)<\/I>/', $contents, $authorsMatch);

            if (count($authorsMatch[0]) !== count($mainMatches[0])) {
                $output->writeln("Authors not found: " . $url);
            }

            for ($i = 0; $i < count($authorsMatch[0]); $i++) {

                $authors = preg_replace("/\(([^()]*+|(?R))*\)/", "", $authorsMatch[1][$i]);
                $authors = html_entity_decode($authors);
                $authors = explode(",", trim($authors));
                foreach ($authors as &$author) {
                    $author = trim($author);
                    $author = preg_replace('/([A-Z][a-z]*)\.([A-Z][a-z]*)/', '$1. $2', $author);
                    $author = preg_replace('/([A-Z][a-z]*)\.([A-Z][a-z]*)/', '$1. $2', $author);
                    $author = trim($author);
                }
                $papers[$mainMatches[1][$i]]["authors"] = $authors;

                $results = $authors;

                $firstAuthor = $results[0];
                // Reform author text to be only the required content.
                if (count($results) > 6) {
                    $text = $firstAuthor . " et al.";
                } else {
                    if (count($results) <= 2) {
                        $text = implode(" and ", $results);
                    } else {
                        // oxford comma
                        $text = implode(', ', array_slice($results, 0, -1)) . ', and ' . end($results);
                    }
                }
                $papers[$mainMatches[1][$i]]["author_str"] = $text;
            }
        }


        // sort on position
        uasort($papers, function ($a, $b) {
            return $a["position"] <=> $b["position"];
        });

        $positions = [];
        foreach ($papers as $code => $paper) {
            $nextPn = 0;
            $pageNumber = $paper['position'];
            if ($paper["published"]) {
                // find next higher pagenumber
                foreach ($papers as $pn) {
                    if ($pn["published"]) {
                        if ($pn["position"] > $pageNumber) {
                            $nextPn = $pn["position"] - 1;
                            break;
                        }
                    }
                }
                if ($nextPn == 0) {
                    $nextPn = 4097;
                }
            }
            $positions[$code] = [...$paper, "position" => $pageNumber . "-" . $nextPn];
        }
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'01']);
        /** @var Reference $reference */
        $counter = 0;
        $total = count($positions);
        foreach ($positions as $code => $paper) {
            $counter += 1;
            $reference = $this->manager->getRepository(Reference::class)->findOneBy(["conference" => $conference, "paperId" => $code]);

            if ($reference === null) {
                $reference = new Reference();
                $reference->setConference($conference);
                $reference->setPaperId($code);
                $this->manager->persist($reference);
                $output->writeln("New reference: " . $code);
            }

            $reference->setTitle($paper["title"]);
            $reference->setAuthor($paper["author_str"]);
            if ($paper["published"]) {
                $reference->setPosition($paper["position"]);
            }
            $reference->setInProc($paper["published"]);
            $reference->setCache($reference->__toString());

            $reference->getAuthors()->clear();
            $authors = new ArrayCollection();
            foreach ($paper["authors"] as $author) {
                $foundAuthors = $this->manager->getRepository(Author::class)->findBy(["name" => $author]);
                if (count($foundAuthors) > 0) {
                    $foundAuthor = $foundAuthors[0];
                } else {
                    $foundAuthor = new Author();
                    $foundAuthor->setName($author);
                    $this->manager->persist($foundAuthor);
                    $output->writeln("New author: " . $author);
                }

                $authors->add($foundAuthor);

                if (!$foundAuthor->getReferences()->contains($reference)) {
                    $foundAuthor->addReference($reference);
                }
                $reference->addAuthor($foundAuthor);
            }
            $output->writeln("Updated reference: " . $code);
            $output->writeln("Processed $counter of $total");
            if ($counter % 100 == 0) {
                $this->manager->flush();
            }
        }
        $this->manager->flush();

        return Command::SUCCESS;
    }
}
