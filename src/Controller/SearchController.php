<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Entity\Search;
use App\Enum\FormatType;
use App\Form\SearchType;
use App\Service\CurrentConferenceService;
use App\Service\FavouriteService;
use MongoDB\Driver\ServerApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class SearchController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, string $searchDb)
    {
        $search = new Search();

        $form = $this->createFormBuilder($search)
            ->add('query', TextareaType::class)
            ->add('formatType', EnumType::class, [
                "class" => FormatType::class,
            ])
            ->setMethod('GET')
            ->getForm();
        $form->handleRequest($request);

        $results = [];

        $apiVersion = new ServerApi(ServerApi::V1);
        $client = new \MongoDB\Client($searchDb, [], ['serverApi' => $apiVersion]);
        try {
            // Send a ping to confirm a successful connection
            $client->selectDatabase('search')->command(['ping' => 1]);
        } catch (Exception $e) {

        }
        $collection = $client->selectCollection("search", "search");
        $searched = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $searched = true;
            $query = $search->getQuery();
            $pipeline = [
                ['$search' => [
                    'index' => 'search',
                    'text' => ['query' => $query, 'path' => ['wildcard' => '*']],
                ]]
            ];

            // Max results 20
            $cursor = $collection->aggregate($pipeline, []);
            foreach ($cursor as $document) {
                $results[] = $document;
                if (count($results) > 20) {
                    break;
                }
            }
        }

        return $this->render("search/index.html.twig", [
            "searched" => $searched,
            "references" => $results,
            "format" => $search->getFormatType()?->value ?? "bibitem",
            "form" => $form->createView(),
            "query" => $search->getQuery()
        ]);
    }
}
