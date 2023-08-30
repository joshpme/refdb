<?php

namespace App\Controller;

use App\Entity\Search;
use App\Enum\FormatType;
use App\Service\FavouriteService;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
    public function indexAction(Request $request, SearchService $searchService, FavouriteService $favouriteService)
    {
        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('query', TextareaType::class)
            ->add('formatType', EnumType::class, [
                "class" => FormatType::class,
            ])
            ->getForm();
        $form->handleRequest($request);

        $results = [];

        $searched = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $searched = true;
            $query = $search->getQuery();
            $results = $searchService->search($query);
        }

        return $this->render("search/index.html.twig", [
            "searched" => $searched,
            "references" => $results,
            "format" => $search->getFormatType()?->value ?? FormatType::Text,
            "form" => $form->createView(),
            "query" => $search->getQuery(),
            "favourites" => $favouriteService->getFavourites(),
        ]);
    }
}
