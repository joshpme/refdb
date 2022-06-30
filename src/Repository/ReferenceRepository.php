<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Search;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ReferenceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReferenceRepository extends EntityRepository
{
    public function findWithAuthors($conference) {
        $query = $this->createQueryBuilder("r");
        $query->leftJoin("r.authors", "a");

        if ($conference instanceof Conference) {
            $query->where("r.conference = :conference")
                ->setParameter("conference", $conference);
        }

        return $query->getQuery()->getResult();
    }

    public function search(Search $search) {
        $query = $this->createQueryBuilder("r");

        $query
            ->join("r.conference","c");

        $searching = false;

        if ($search->getConference() !== null) {
            $searching = true;
            $conference = str_replace("’","'",mb_strtolower($search->getConference()));
            $query
                ->andWhere("LOWER(c.code) LIKE :conf")
                ->orWhere("LOWER(c.name) LIKE :conf")
                ->setParameter("conf", "%" . $conference . "%");
        }

        if ($search->getLocation() !== null) {
            $searching = true;
            $query->andWhere("LOWER(c.location) LIKE :location")
                ->setParameter("location", "%" . mb_strtolower($search->getLocation()) . "%");
        }

        if ($search->getDate() !== null) {
            $searching = true;
            $query->andWhere("LOWER(c.year) LIKE :year")
                ->setParameter("year", "%" . mb_strtolower($search->getDate()) . "%");
        }

        if ($search->getPaperId() !== null) {
            $searching = true;
            $query->andWhere("LOWER(r.paperId) LIKE :paperId")
                ->setParameter("paperId", "%" . mb_strtolower($search->getPaperId()) . "%");
        }

        if ($search->getTitle() !== null) {
            $searching = true;
            $query->andWhere("LOWER(r.title) LIKE :title")
                ->setParameter("title", "%" . mb_strtolower($search->getTitle()) . "%");
        }
        if ($search->getAuthor() !== null) {
            $authors = explode(",",$search->getAuthor());
            $i = 0;
            foreach ($authors as $author) {
                $author = trim($author);
                if (strlen($author) > 0) {
                    $author = mb_strtoupper(substr($author,0,1)) . substr($author,1);
                    $frontTrim = "%";
                    if (strpos($author,".") !== false) {
                        $frontTrim = "";
                    }
                    $query->andWhere("0 < (SELECT COUNT(a$i.id) FROM App:Author a$i INNER JOIN a$i.references ar$i WHERE ar$i.id = r.id AND a$i.name LIKE :name$i)")
                        ->setParameter("name$i", $frontTrim . trim($author) . "%");
                    $i++;
                }
            }

            $searching = true;
        }

        if ($searching == false) {
            return false;
        }


        return $query;
    }

}
