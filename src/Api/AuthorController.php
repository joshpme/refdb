<?php

namespace App\Api;

use App\Entity\Author;
use App\Entity\Reference;
use App\Form\AuthorType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * Author controller.
 *
 * @Route("api/authors", name="api_author")
 */
class AuthorController extends ApiController
{
    /**
     * @Route("/{id}", name="_get", methods={"GET"})
     * @param Author $author
     * @return JsonResponse
     */
    public function getAction(Author $author)
    {
        $response = new JsonResponse($author);
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
        $author = new Author();

        $form = $this->createForm(AuthorType::class, $author, ["csrf_protection"=>false]);
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($author);
            $manager->flush();
            return $this->respondSuccess(
                ApiController::CREATED_CODE,
                $author,
                "api_author_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_put", methods={"PUT"})
     * @param Request $request
     * @param Author $author
     * @return Response
     */
    public function putAction(Request $request, Author $author) {
        $dto = $this->getDto($request);

        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm(AuthorType::class, $author, ["csrf_protection"=>false]);
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
            return $this->respondSuccess(
                ApiController::UPDATE_CODE,
                $author,
                "api_author_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_delete", methods={"DELETE"})
     * @param Author $author
     * @return Response
     */
    public function deleteAction(Author $author) {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($author);
        $manager->flush();

        return $this->respondSuccess(ApiController::DELETE_CODE);
    }

    /**
     * @Route("/{id}/references", name="_list_references", methods={"GET"})
     * @param Author $author
     * @return JsonResponse
     */
    public function referencesAction(Author $author)
    {
        $references = $author->getReferences();
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
