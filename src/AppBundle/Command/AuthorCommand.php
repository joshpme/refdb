<?php

namespace AppBundle\Command;

use AppBundle\Entity\Author;
use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Service\AuthorService;
use AppBundle\Service\ImportService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import conferences via command line
 *
 * Class ImportCommand
 * @package AppBundle\Command
 */
class AuthorCommand extends Command
{
    private $manager;
    private $authorService;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:authors';

    public function __construct(ObjectManager $manager, AuthorService $authorService)
    {
        $this->manager = $manager;
        $this->authorService = $authorService;
        parent::__construct();
    }


    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        $manager = $this->manager;

        /** @var Reference[] $results */
        $references = $manager->getRepository(Reference::class)->findAll();

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $reference->setAuthors(new ArrayCollection());
        }
        $manager->flush();
        $manager->clear();

        $authors = $manager->getRepository(Author::class)->findAll();

        /** @var Reference $reference */
        foreach ($authors as $author) {
            $manager->remove($author);
        }
        $manager->flush();
        $manager->clear();

        $references = $manager->getRepository(Reference::class)->findAll();
        $results = $this->findAuthors($references, $this->authorService);
        $newAuthors = $results['authors'];

        // All authors should be new.
        foreach ($newAuthors as $name => $newAuthorRefs) {
            $newAuthor = new Author();
            $newAuthor->setName($name);
            foreach ($newAuthorRefs as $reference) {
                if (!$newAuthor->getReferences()->contains($reference)) {
                    $newAuthor->addReference($reference);
                }
            }
            $manager->persist($newAuthor);
        }

        $manager->flush();
    }

    private function findAuthors($references, AuthorService $authorService)
    {
        $authors = [];

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $results = $authorService->parse($reference->getOriginalAuthors());

            $reference->setAuthor($results['text']);

            foreach ($results['authors'] as $author) {
                if (!isset($authors[$author])) {
                    $authors[$author] = [];
                }
                $authors[$author][] = $reference;
            }
        }

        return ["references" => $references, "authors" => $authors];
    }

}