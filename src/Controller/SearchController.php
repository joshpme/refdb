<?php

namespace App\Controller;

use App\Entity\Search;
use App\Enum\FormatType;
use App\Service\ExternalSearch;
use App\Service\FavouriteService;
use App\Service\MarkupReference;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 */
class SearchController extends AbstractController
{

    /**
     * @Route("/external/{format}", name="external-query", defaults={"format": "text"})
     * @param Request $request
     * @return JsonResponse
     */
    public function externalAction(Request $request, ExternalSearch $externalSearch, ?string $format = "text")
    {
        $query = $request->get('query');
        $externalResult = $externalSearch->search($query);

        if (!empty($externalResult)) {
            $formatter = new MarkupReference();
            $externalResult["reference"] = match (FormatType::from($format)){
                FormatType::Text => $externalResult["reference"],
                FormatType::BibTex => $externalSearch->getBibTex($externalResult["doi"]),
                FormatType::BibItem => $formatter->latex($externalResult["reference"], $externalResult["abbreviation"]),
                FormatType::Word => $formatter->word($externalResult["reference"], $externalResult["abbreviation"]),
            };
        }

        return new JsonResponse(['query'=>$externalResult]);
    }

    /**
     * @Route("/internal", name="internal-query")
     * @param Request $request
     * @return JsonResponse
     */
    public function internalAction(Request $request, SearchService $searchService)
    {
        $query = $request->get('query');
        return new JsonResponse(['query'=>$searchService->search($query)]);
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, SearchService $searchService, ExternalSearch $externalSearch, FavouriteService $favouriteService)
    {
        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('query', TextareaType::class)
            ->add('formatType', EnumType::class, [
                "class" => FormatType::class,
            ])
            ->add('checkExternal', CheckboxType::class, [
                "label" => "Search for external reference",
                "required" => false,
            ])
            ->getForm();
        $form->handleRequest($request);

        $results = [];

        $searched = false;
        $externalResult = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $searched = true;
            $query = $search->getQuery();

            if ($search->getCheckExternal()) {
                $externalResult = $externalSearch->search($query);

                if (!empty($externalResult)) {
                    $formatter = new MarkupReference();
                    $externalResult["reference"] = match ($search->getFormatType()){
                        FormatType::Text => $externalResult["reference"],
                        FormatType::BibTex => $externalSearch->getBibTex($externalResult["doi"]),
                        FormatType::BibItem => $formatter->latex($externalResult["reference"], $externalResult["abbreviation"]),
                        FormatType::Word => $formatter->word($externalResult["reference"], $externalResult["abbreviation"]),
                    };
                }
            } else {
                $results = $searchService->search($query);
            }
        }

        return $this->render("search/index.html.twig", [
            "searched" => $searched,
            "references" => $results,
            "format" => $search->getFormatType()?->value ?? FormatType::Text,
            "form" => $form->createView(),
            "query" => $search->getQuery(),
            "favourites" => $favouriteService->getFavourites(),
            "external" => $externalResult,
        ]);
    }
}
