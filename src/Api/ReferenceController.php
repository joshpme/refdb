<?php

namespace App\Api;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
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
 * @Route("api", name="api_reference")
 */
class ReferenceController extends AbstractController
{
    /**
     * @Route("/conference/{id}/references", name="_by_conference", methods={"GET"})
     * @param Conference $conference
     * @return JsonResponse
     */
    public function conferenceAction(Conference $conference)
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

    /**
     * @Route("/reference/{id}", name="_get", methods={"GET"})
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
     * @Route("/reference/", name="_post", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request) {

        $manager = $this->getDoctrine()->getManager();
        $referenceDto = [];
        if ($content = $request->getContent()) {
            $referenceDto = json_decode($content, true);
        }
        $reference = new Reference();
        $reference->updateFromDto($referenceDto);

        /** @var AuthorRepository $authorRepo */
        $authorRepo = $this->getDoctrine()->getManager()->getRepository(Author::class);

        if (isset($referenceDto['authors'])) {
            $authors = new ArrayCollection();
            foreach ($referenceDto['authors'] as $author) {
                $id = $author['id'] ?? null;
                $name = $author['name'] ?? null;
                $author = $authorRepo->findOrCreate($id, $name);
                $authors->add($author);
                $manager->persist($author);
            }
            $reference->setAuthors($authors);
        }

        if (isset($referenceDto['conference'])) {
            /** @var Conference $conference */
            $conference = $this->getDoctrine()->getManager()->getRepository(Conference::class)->find($referenceDto['conference']);
            if ($conference === null) {
                return new JsonResponse(["error"=> "Conference not found"], 404);
            }
            $reference->setConference($conference);
        }

        $manager->persist($reference);
        $manager->flush();

        return new JsonResponse($reference->jsonSerialize(true));
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/reference/{id}", name="_patch", methods={"PATCH"})
     * @param Reference $reference
     * @param Request $request
     * @return Response
     */
    public function patchAction(Reference $reference, Request $request) {

        $manager = $this->getDoctrine()->getManager();
        $referenceDto = [];
        if ($content = $request->getContent()) {
            $referenceDto = json_decode($content, true);
        }
        $reference->updateFromDto($referenceDto);
        /** @var AuthorRepository $authorRepo */
        $authorRepo = $this->getDoctrine()->getManager()->getRepository(Author::class);

        if (isset($referenceDto['authors'])) {
            $authors = new ArrayCollection();
            foreach ($referenceDto['authors'] as $author) {
                $id = $author['id'] ?? null;
                $name = $author['name'] ?? null;
                $author = $authorRepo->findOrCreate($id, $name);
                $authors->add($author);
                $manager->persist($author);
            }
            $reference->setAuthors($authors);
        }

        if (isset($referenceDto['conference'])) {
            /** @var Conference $conference */
            $conference = $this->getDoctrine()->getManager()->getRepository(Conference::class)->find($referenceDto['conference']);
            if ($conference === null) {
                return new JsonResponse(["error"=> "Conference not found"], 404);
            }
            $reference->setConference($conference);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($reference->jsonSerialize(true));
    }
}
