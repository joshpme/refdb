<?php

namespace App\Api;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Form\ConferenceType;
use App\Form\ReferenceType;
use App\Form\Type\TagsAsInputType;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Conference controller.
 *
 * @Route("api/references", name="api_reference")
 */
class ReferenceController extends ApiController
{
    /**
     * @Route("/{id}", name="_get", methods={"GET"})
     * @param Reference $reference
     * @return JsonResponse
     */
    public function getAction(Reference $reference)
    {
        $response = new JsonResponse($reference->jsonSerialize(true));
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
        $dto['authors'] = json_encode($dto['authors']);
        $manager = $this->getDoctrine()->getManager();
        $reference = new Reference();

        $form = $this->createForm(ReferenceType::class, $reference, ["csrf_protection"=>false])
            ->add('authors', TagsAsInputType::class, [
                "entity_class"=> Author::class,
                "data_source" => "author_search",
                "label"=> "Associated Authors (un-ordered)"]);;
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($reference);
            $manager->flush();
            return $this->respondSuccess(
                ApiController::CREATED_CODE,
                $reference->jsonSerialize(true),
                "api_reference_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_put", methods={"PUT"})
     * @param Request $request
     * @param Reference $reference
     * @return Response
     */
    public function putAction(Request $request, Reference $reference) {
        $dto = $this->getDto($request);
        $dto['authors'] = json_encode($dto['authors']);
        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm(ReferenceType::class, $reference, ["csrf_protection"=>false])
            ->add('authors', TagsAsInputType::class, [
                "entity_class"=> Author::class,
                "data_source" => "author_search",
                "label"=> "Associated Authors (un-ordered)"]);;
        $form->submit($dto);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
            return $this->respondSuccess(
                ApiController::UPDATE_CODE,
                $reference->jsonSerialize(true),
                "api_reference_get");
        }
        return $this->responseFormErrors($form);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_patch", methods={"PATCH"})
     * @param Request $request
     * @param Reference $reference
     * @return Response
     */
    public function patchAction(Request $request, Reference $reference) {
        $manager =
            $this->getDoctrine()
            ->getManager();

        $dto = $this->getDto($request);

        $reference->updateFromDto($dto);

        if (isset($dto['conference'])) {
            $conference =
                $manager
                    ->getRepository(Conference::class)
                    ->find($dto['conference']);
            if ($conference === null) {
                return new JsonResponse(["error"=> "Conference not found"], 404);
            }
        }

        if (isset($dto['authors'])) {
            /** @var AuthorRepository $authorRepo */
            $authorRepo = $manager->getRepository(Author::class);
            $authors = new ArrayCollection();
            foreach ($dto['authors'] as $author) {
                $id = $author['id'] ?? null;
                $name = $author['name'] ?? null;
                $author = $authorRepo->findOrCreate($id, $name);
                $authors->add($author);
                $manager->persist($author);
            }
            $reference->setAuthors($authors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->respondSuccess(
            ApiController::UPDATE_CODE,
            $reference->jsonSerialize(true),
            "api_conference_get");
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="_delete", methods={"DELETE"})
     * @param Reference $reference
     * @return Response
     */
    public function deleteAction(Reference $reference) {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($reference);
        $manager->flush();

        return $this->respondSuccess(ApiController::DELETE_CODE);
    }
}
