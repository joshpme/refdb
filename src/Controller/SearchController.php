<?php

namespace App\Controller;

use App\Entity\Search;
use App\Enum\FormatType;
use App\Form\OmniSearchType;
use App\Service\ExternalSearch;
use App\Service\FavouriteService;
use App\Service\MarkupReference;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

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
     * @Route("/internal/{format}", name="internal-query", defaults={"format": "text"})
     * @param Request $request
     * @return JsonResponse
     */
    public function internalAction(Request $request, SearchService $searchService, Environment $twig, ?string $format = "text")
    {
        $query = $request->get('query');
        $response = $searchService->search($query);
        $results = [];
        foreach ($response as $reference) {
            $result = $reference->jsonSerialize();
            if (FormatType::from($format) == FormatType::BibItem) {
                $result['name'] = $twig->render("reference/latex.html.twig", ["reference"=>$reference, "form"=>"short", "hide_header"=>true]);
            }
            $results[] = $result;
        }
        return new JsonResponse(['query'=>$results]);
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, SearchService $searchService, ExternalSearch $externalSearch, FavouriteService $favouriteService)
    {
        $search = new Search();
        $form = $this->createForm(OmniSearchType::class, $search);
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
