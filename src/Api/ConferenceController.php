<?php

namespace App\Api;

use App\Entity\Conference;
use App\Entity\Reference;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * Conference controller.
 *
 * @Route("api", name="api_conference")
 */
class ConferenceController extends AbstractController
{
    /**
     * Finds and displays a conference entity.
     *
     * @Route("/conference/list", name="_list", methods={"GET"})
     * @return JsonResponse
     */
    public function listAction()
    {
        $conferenceDb = $this->getDoctrine()->getManager()->getRepository(Conference::class)->findAll();
        $response = new JsonResponse($conferenceDb);
        $response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );
        return $response;
    }

    /**
     * Finds and displays a conference entity.
     *
     * @Route("/conference/{id}", name="_get", methods={"GET"})
     * @param Conference $conference
     * @return JsonResponse
     */
    public function getAction(Conference $conference)
    {
        $response = new JsonResponse($conference);
        $response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );
        return $response;
    }

    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/conference/", name="_post", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request) {

        $conferenceDto = [];
        if ($content = $request->getContent()) {
            $conferenceDto = json_decode($content, true);
        }
        $conference = new Conference();
        $conference->updateFromDto($conferenceDto);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($conference);
    }

    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/conference/{id}", name="_patch", methods={"PATCH"})
     * @param Conference $conference
     * @param Request $request
     * @return Response
     */
    public function patchAction(Conference $conference, Request $request) {

        $conferenceDto = [];
        if ($content = $request->getContent()) {
            $conferenceDto = json_decode($content, true);
        }
        $conference->updateFromDto($conferenceDto);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($conference);
    }
}
