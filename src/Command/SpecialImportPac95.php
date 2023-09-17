<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportPac95 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-pac-95';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $lines = file("src/DataFixtures/Import/pac95.txt");

        // if line doesnt end with a paper code remove the new line
        foreach ($lines as $key => $line) {
            if (!preg_match("/[A-Z]{3}[0-9]{2}/", $line)) {
                $lines[$key] = rtrim($line) . " ";
            }
        }
        $content = implode("", $lines);

        // check if there are two series of periods
        $lines = explode("\n", $content);


        $papers = [];
        foreach ($lines as $line) {
            assert(preg_match("/\.{3,}(.*)[A-Z0-9a-z](.*)\.{3,}/", $line) === 0);
            $content = trim($line);

            //Bunch Lengthening Thresholds on the Daresbury SRS — J.A. Clarke......................................... 3128 WAC23
            if (preg_match("/(.*?)—(.*?)\.{2,}\s*([0-9]+)\s+([A-Z]{3}[0-9]{2})/", $content, $matches)) {
                assert(filter_var($matches[3], FILTER_VALIDATE_INT) !== false);
                $papers[$matches[4]] = [
                    "title" => trim($matches[1]),
                    "authors" => trim($matches[2]),
                    "original_author_str" => trim($matches[2]),
                    "page" => trim($matches[3]),
                    "code" => trim($matches[4])
                ];
            } else {
                $output->writeln("Did not match: $content");
            }
        }

        // sort by page
        uasort($papers, function ($a, $b) {
            return $a['page'] <=> $b['page'];
        });

        // create page position
        foreach ($papers as $code => &$paper) {
            $nextPn = 0;
            // find next higher page number
            foreach ($papers as $pn) {
                if (filter_var($pn['page'], FILTER_VALIDATE_INT) && $pn['page'] > $paper['page']) {
                    $nextPn = $pn['page'] - 1;
                    break;
                }
            }
            if ($nextPn == 0) {
                $nextPn = $paper['page'] + 3;
            }
            $paper['position'] = $paper['page'] . "-" . $nextPn;
        }

        $fixAuthor = function($author) {
            $author = trim($author);
            $author = preg_replace("/^(\p{Lu})\p{Ll}+\s((\p{Lu}\.\s)?[A-Z\p{Lu}][\p{L}']+)$/u", "$1. $2", $author);
            //assert($fixAuthor("Yong-Chul Chae") === "Y. C. Chae");
            $author = preg_replace("/^(\p{Lu})\p{Ll}+\-(\p{Lu})[a-z\p{Ll}]+\s(\p{Lu}[\p{L}']+)$/u", "$1. $2. $3", $author);
            // assert($fixAuthor("J.A. Clarke") === "J. A. Clarke");
            $author = preg_replace("/^(\p{Lu}\p{Ll}*)\.(\p{Lu})\.\s(\p{Lu}[\p{L}']+)$/u", "$1. $2. $3", $author);
            return trim($author);
        };

        assert($fixAuthor("André Verdier") === "A. Verdier");
        assert($fixAuthor("J.A. Clarke") === "J. A. Clarke");
        assert($fixAuthor("Glenn Decker") === "G. Decker");
        assert($fixAuthor("Yong-Chul Chae") === "Y. C. Chae");
        assert($fixAuthor("Steven J. Werkema") === "S. J. Werkema");
        assert($fixAuthor("Ian Hsu") === "I. Hsu");
        assert($fixAuthor("Yu.P. Virchenko") === "Yu. P. Virchenko");
        assert($fixAuthor("D.-P. Deng") === "D.-P. Deng");
        assert($fixAuthor("Patrick G. O'Shea") === "P. G. O'Shea");

        $authorStr = function($authors) {
            $firstAuthor = $authors[0];
            // Reform author text to be only the required content.
            if (count($authors) > 6) {
                $text = $firstAuthor . " et al.";
            } else {
                if (count($authors) <= 2) {
                    $text = implode(" and ", $authors);
                } else {
                    // oxford comma
                    $text = implode(', ', array_slice($authors, 0, -1)) . ', and ' . end($authors);
                }
            }
            return $text;
        };

        // find authors
        foreach ($papers as &$paper) {
            $authors = explode(",", $paper['authors']);
            foreach ($authors as &$author) {
                $author = $fixAuthor($author);
            }
            $paper['authors'] = $authors;

            $paper['author_str'] = $authorStr($authors);
        }


        // fix paper titles
        foreach ($papers as &$paper) {
            $paper['title'] = str_replace(" (Invited)", "",$paper['title']);
            $paper['title'] = preg_replace("/\s+/", " ", $paper['title']);
        }

        /**
         * @var Conference $conference
         */

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'95']);

        $references = $conference->getReferences();

        $changes = 0;
        /** @var Reference $reference */
        foreach ($references as $reference) {
            $code = $reference->getPaperId();
            if (isset($papers[$code])) {

                if ($reference->getTitle() !== $papers[$code]['title']) {

                    $output->writeln("Title mismatch: " . $reference->getTitle() . " vs " . $papers[$code]['title']);
                    $reference->setTitle($papers[$code]['title']);
                }

                if ($reference->getPosition() !== $papers[$code]['position']) {
                    $output->writeln("Position mismatch: " . $reference->getPosition() . " vs " . $papers[$code]['position']);
                    $reference->setPosition($papers[$code]['position']);
                }

                if ($reference->getAuthor() !== $papers[$code]['author_str']) {
                    $output->writeln("Author mismatch: " . $reference->getAuthor() . " vs " . $papers[$code]['author_str']);
                    $reference->setAuthor($papers[$code]['author_str']);
                }

                $reference->setOriginalAuthors($papers[$code]['original_author_str']);

                foreach ($reference->getAuthors() as $author) {
                    $author->removeReference($reference);
                    if ($author->getReferences()->count() == 0) {
                        $this->manager->remove($author);
                        $output->writeln("Removed author: " . $author->getName());
                    }
                }
                foreach ($papers[$code]['authors'] as $result) {
                    $foundAuthors = $this->manager->getRepository(Author::class)->findBy(["name" => $result]);
                    if (count($foundAuthors) > 0) {
                        $foundAuthor = $foundAuthors[0];
                    } else {
                        $foundAuthor = new Author();
                        $foundAuthor->setName($result);
                        $this->manager->persist($foundAuthor);
                        $output->writeln("New author: " . $result);
                    }

                    if (!$foundAuthor->getReferences()->contains($reference)) {
                        $foundAuthor->addReference($reference);
                    }
                    $reference->addAuthor($foundAuthor);
                }

                $reference->setCache($reference->__toString());
                $changes++;
            } else {
                $output->writeln("Missing: " . $code);
            }

            if ($changes % 100 == 0) {
                $output->writeln("Updating references. Up to: " . $changes);
                $this->manager->flush();
            }
        }

        $this->manager->flush();

        return Command::SUCCESS;
    }
}
