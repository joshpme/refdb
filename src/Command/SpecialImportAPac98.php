<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportAPac98 extends Command
{
    private EntityManagerInterface $manager;

    protected static $defaultName = 'app:special-import-apac-98';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = "http://accelconf.web.cern.ch/a98/Contents.html";

        $contents = file_get_contents($url);

        /**
         * <TD VALIGN=TOP WIDTH="9%" HEIGHT="7"><FONT FACE="Times New Roman,Times"><FONT SIZE=+0><A HREF="APAC98/6D027.PDF">6d027</A></FONT></FONT></TD>
         * <TD VALIGN=TOP COLSPAN="2" WIDTH="86%" HEIGHT="7"><FONT FACE="Times New Roman,Times"><FONT SIZE=+0>Subpicosecond
         * Electron Beam Diagnostics by Coherent Transition Radiation Interferometer</FONT></FONT></TD>
         * <TD VALIGN=TOP COLSPAN="3" WIDTH="6%" HEIGHT="7">
         * <DIV ALIGN=right><FONT FACE="Times New Roman,Times"><FONT SIZE=+0>740</FONT></FONT></DIV
         */

        preg_match_all('/APAC98\/([a-zA-Z0-9]+)\.PDF">.*?<FONT SIZE=\+0>(.*?)<\/FONT>.*?<FONT SIZE=\+0>([0-9]+)<\/FONT>/is', $contents, $matches, PREG_SET_ORDER);


        $papers = [];
        $titles = [];
        foreach ($matches as $match) {
            $papers[$match[1]] = $match[3];
            $title = preg_replace("/\s+/", " ", str_replace("\x1D", " ", $match[2]));
            $titles[$match[1]] = html_entity_decode($title);
        }
        $output->writeln("Processed " . $url);

        $positions = [];
        foreach ($papers as $code => $paper) {
            $nextPn = 0;
            // find next higher pagenumber
            foreach ($papers as $pn) {
                if (filter_var($pn, FILTER_VALIDATE_INT) && $pn > $paper) {
                    $nextPn = $pn - 1;
                    break;
                }
            }
            if ($nextPn == 0) {
                $nextPn = 899 + 5;
            }
            $positions[$code] = $paper . "-" . $nextPn;
        }

        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'APAC\'98']);

        /** @var Reference $reference */
        foreach ($conference->getReferences() as $reference) {
            if (isset($positions[$reference->getPaperId()])) {
                $paper = $positions[$reference->getPaperId()];
                $reference->setPosition($paper);
                $reference->setTitle($titles[$reference->getPaperId()]);
                $reference->setCache($reference->__toString());

                $ogAuthors = $reference->getOriginalAuthors();

                $reference->getAuthors()->clear();
                $authors = explode(",", $ogAuthors);

                foreach ($authors as &$author) {
                    $author = trim($author);
                    $author = preg_replace("/\s+/", " ", $author);
                    $author = preg_replace("/^([A-Z])[A-Z]{2,20} ([A-Z][a-z\-]+)$/", "$1. $2", $author);
                    $author = preg_replace("/^([A-Z])[a-zA-Z\-]+ ([A-Z][a-z\-]+)$/", "$1. $2", $author);
                    $author = preg_replace('/([A-Z][a-z]*)\.([A-Z][a-z]*)/', '$1. $2', $author);
                    $author = preg_replace('/([A-Z][a-z]*)\.([A-Z][a-z]*)/', '$1. $2', $author);
                }
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
                $reference->setAuthor($text);

                foreach ($results as $result) {
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
                    $reference->setCache($reference->__toString());
                }

                //dump($authors);
            } else {
                $output->writeln("Missing " . $reference->getPaperId());
            }
        }



        $this->manager->flush();
        return Command::SUCCESS;
    }
}
