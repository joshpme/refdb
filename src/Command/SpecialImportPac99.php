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

/**
 * Class ImportCommand
 * @package App\Command
 */
class SpecialImportPac99 extends Command
{
    private EntityManagerInterface $manager;
    private AuthorService $authorService;

    protected static $defaultName = 'app:special-import-pac-99';

    public function __construct(EntityManagerInterface $manager, AuthorService $authorService)
    {
        $this->authorService = $authorService;
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseUrl = "http://accelconf.web.cern.ch/p99/";
        $urls = ["vol1.htm", "vol2.htm", "vol3.htm", "vol4.htm", "vol5.htm"];

        $papers = [];
        foreach ($urls as $url) {
            $content = file_get_contents($baseUrl . $url);

            /**
            <A HREF="PAPERS/THP11.PDF">
            <STRONG>Commissioning Status of the KEKB Linac</STRONG>
            </A>
            --
            <em>Y.Ogawa, Linac Commissioning Group, KEK, Tsukuba, Ibaraki, Japan</em></TD>
            <TD ALIGN="right" VALIGN="top">
            2984</TD>
             */

            preg_match_all("/\/([A-Z0-9]+)\.PDF.*?<STRONG>(.*?)<\/STRONG>.*?<em>(.*?)<\/em>.*?<TD\s+ALIGN=\"right\"\s+VALIGN=\"top\">\s+([0-9]+)/xs", $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $authors = explode(",", $match[3]);

                foreach ($authors as &$author) {
                    $author = preg_replace("/^([A-Z])[a-z]+\s([A-Z][a-z]+)$/","$1. $2", $author);
                    $author = explode(" for the ", $author)[0];
                    $author = explode(" and the ", $author)[0];
                    $author = explode(" on behalf of ", $author)[0];
                    $author = preg_replace("/\(([^()]*+|(?R))*\)/", "", $author);
                    $author = preg_replace("/([A-Z]{1,2})\.([A-Z][a-z]+)/", "$1. $2", $author);

                }

                $authors = $this->authorService->parse(implode(", ", $authors));
                $papers[$match[1]] = [
                    "authors" => $authors,
                    "title" => html_entity_decode(strip_tags(preg_replace("/\s+/", " ", $match[2]))),
                    "page" => $match[4],
                    "author_str" => $match[3],
                ];
            }
        }

        // sort by page number
        uasort($papers, function ($a, $b) {
            return $a['page'] <=> $b['page'];
        });

        foreach ($papers as $code => &$paper) {
            $nextPn = 0;

            // find next higher pagenumber
            foreach ($papers as $pn) {
                if ($pn['page'] > $paper['page']) {
                    $nextPn = $pn['page'] - 1;
                    break;
                }
            }
            if ($nextPn == 0) {
                $nextPn = 3778;
            }
            $paper['position'] = $paper['page'] . "-" . $nextPn;
        }

        /**
         * @var Conference $conference
         */
        $conference = $this->manager->getRepository(Conference::class)->findOneBy(['code' => 'PAC\'99']);

        $references = $conference->getReferences();

        $changes = 0;
        /** @var Reference $reference */
        foreach ($references as $reference) {
            $code = $reference->getPaperId();
            if (isset($papers[$code])) {
                $reference->setAuthor($papers[$code]['authors']['text']);
                $reference->setTitle($papers[$code]['title']);
                $reference->setOriginalAuthors($papers[$code]['author_str']);
                $reference->setPosition($papers[$code]['position']);
                $reference->getAuthors()->clear();
                foreach ($papers[$code]['authors']['authors'] as $result) {
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
                $output->writeln("No position for: " . $code);
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
