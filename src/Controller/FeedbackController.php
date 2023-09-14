<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Reference;
use App\Entity\User;
use App\Service\AdminNotifyService;
use App\Service\FeedbackNotifyService;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Feedback controller.
 */
class FeedbackController extends AbstractController
{
    /**
     * Lists all feedback entities.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/feedback", name="feedback_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(Feedback::class)
            ->createQueryBuilder("f")
            ->innerJoin("f.reference", "r")
            ->where('f.resolved = false')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('feedback/index.html.twig', array('pagination' => $pagination));
    }

    /**
     * Creates a new feedback entity.
     * @Route("/feedback/{id}", name="feedback_new")
     * @param Request $request
     * @param Reference $reference
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Reference $reference, EntityManagerInterface $manager, AdminNotifyService $notifyService)
    {
        $feedback = new Feedback();

        if ($this->getUser() !== null && $this->getUser() instanceof User) {
            $feedback->setEmail($this->getUser()->getEmail());
        }
        /** @var Reference $reference */
        $feedback->setReference($reference);

        $feedback->setAuthor($reference->getAuthor());
        $feedback->setTitle($reference->getTitle());
        $feedback->setPosition($reference->getPosition());
        $feedback->setCustomDoi($reference->doiOnly());

        $form = $this->createForm('App\Form\FeedbackType', $feedback, ["action" => $this->generateUrl("feedback_new", [
            "id" => $reference->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($feedback->getFeedback() === null &&
                $feedback->getAuthor() == $reference->getAuthor() &&
                $feedback->getTitle() == $reference->getTitle() &&
                $feedback->getPosition() == $reference->getPosition() &&
                $feedback->getCustomDoi() == $reference->doiOnly()) {
                $form->addError(new \Symfony\Component\Form\FormError("You must provide feedback or change the reference details."));
            }

            if ($form->isValid()) {
                $manager->persist($feedback);
                $manager->flush();

                $notifyService->newFeedback($reference, $feedback);

                $this->addFlash("notice", "Your feedback has been sent to our administrators. Thank you.");
                return new JsonResponse([
                    "success" => true,
                    "redirect" => $this->generateUrl("reference_show", array('id' => $reference->getId()))]);
            }
        }

        return $this->render('feedback/new.html.twig', array(
            'feedback' => $feedback,
            'form' => $form->createView(),
        ));
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/feedback/resolve/{id}", name="feedback_resolve")
     */
    public function resolveAction(Feedback $feedback, EntityManagerInterface $manager): Response
    {
        $feedback->setResolved(true);
        $manager->flush();
        return $this->redirectToRoute("feedback_index");
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/admin/feedback/apply/{id}", name="feedback_apply")
     */
    public function applyAction(Feedback $feedback, EntityManagerInterface $manager, SearchService $searchService, FeedbackNotifyService $feedbackNotifyService): Response
    {
        $reference = $feedback->getReference();

        $reference->setAuthor($feedback->getAuthor());
        $reference->setTitle($feedback->getTitle());
        $reference->setPosition($feedback->getPosition());
        $reference->setCustomDoi($feedback->getCustomDoi());
        $feedback->setResolved(true);
        $reference->setCache($reference->__toString());
        $searchService->insertOrUpdate($reference);
        $manager->flush();
        $this->addFlash("success", "Feedback applied.");

        $feedbackNotifyService->receipt($reference, $feedback);

        return $this->redirectToRoute("feedback_show", ["id" => $feedback->getId()]);
    }

    /**
     * @Route("/feedback/show/{id}", name="feedback_show")
     */
    public function showAction(Feedback $feedback): Response
    {
        return $this->render('feedback/show.html.twig', array(
            'feedback' => $feedback,
            'reference' => $feedback->getReference()
        ));
    }
}
