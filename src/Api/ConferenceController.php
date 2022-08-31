<?php

namespace App\Api;

use App\Entity\Conference;
use App\Entity\Reference;
use App\Form\ConferenceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * Conference controller.
 *
 * @Route("api/conferences", name="api_conference")
 */
class ConferenceController extends ApiController
{
    /**
     * @Route("/", name="_list", methods={"GET"})
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
     * @Route("/{id}", name="_get", methods={"GET"})
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
     * @IsGranted("ROLE_ADMIN")
     * @Route("/", name="_post", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request) {
        $dto = $this->getDto($request);

        $manager = $this->getDoctrine()->getManager();
        $conference = new Conference();

        $form = $this->createForm(ConferenceType::class, $conference, ["csrf_protection"=>false]);
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($conference);
            $manager->flush();
            return $this->respondSuccess(
                ApiController::CREATED_CODE,
                $conference,
                "api_conference_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_put", methods={"PUT"})
     * @param Request $request
     * @param Conference $conference
     * @return Response
     */
    public function putAction(Request $request, Conference $conference) {
        $dto = $this->getDto($request);

        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm(ConferenceType::class, $conference, ["csrf_protection"=>false]);
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
            return $this->respondSuccess(
                ApiController::UPDATE_CODE,
                $conference,
                "api_conference_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_patch", methods={"PATCH"})
     * @param Conference $conference
     * @param Request $request
     * @return Response
     */
    public function patchAction(Request $request, Conference $conference) {
        $dto = $this->getDto($request);
        $conference->updateFromDto($dto);
        $this->getDoctrine()->getManager()->flush();

        return $this->respondSuccess(
            ApiController::UPDATE_CODE,
            $conference,
            "api_conference_get");
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_delete", methods={"DELETE"})
     * @param Conference $conference
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Conference $conference) {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($conference);
        $manager->flush();

        return $this->respondSuccess(ApiController::DELETE_CODE);
    }

    /**
     * @Route("/{id}/references", name="_list_references", methods={"GET"})
     * @param Conference $conference
     * @return JsonResponse
     */
    public function referencesAction(Conference $conference)
    {
        $references = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository(Reference::class)
            ->findWithAuthors($conference);
        $output = [];
        /** @var Reference $ref */
        foreach ($references as $ref) {
            $output[] = $ref->jsonSerialize(true);
        }
        $response = new JsonResponse($output);
        $response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );
        return $response;
    }
}
