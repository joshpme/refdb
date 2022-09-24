<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Favourite;
use App\Entity\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\AuthorService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Clean controller, for basic data cleansing purposes
 *
 * @Route("clean")
 */
class CleanController extends AbstractController
{
    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/talks/{id}/{talk}", name="talk_update", options={"expose"=true})
     */
    public function updatePossibleTalkAction(Reference $reference, bool $talk) {
        $manager = $this->getDoctrine()->getManager();
        $reference->setInProc($talk);
        $reference->setConfirmedInProc(1);
        $manager->flush();
        return new JsonResponse();
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/talks", name="talk_clean")
     */
    public function talkAction(Request $request, PaginatorInterface $paginator)
    {
        $allPossibleTalks = $this->getDoctrine()->getManager()
            ->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r.id")
            ->addSelect('SIZE(r.authors) as HIDDEN authorsCount')
            ->where('r.position is null or r.position = :empty')
            ->andWhere('r.inProc = 1')
            ->andWhere('r.confirmedInProc is null')
            ->setParameter("empty", "")
            ->having('authorsCount = 1')
            ->getQuery()
            ->getArrayResult(); 
        
        $count = count($allPossibleTalks);
        $referenceId = array_rand($allPossibleTalks);

        $reference = $this->getDoctrine()->getManager()
            ->getRepository(Reference::class)
            ->find($allPossibleTalks[$referenceId]['id']);

        // parameters to template
        return $this->render('clean/talk.html.twig', array('reference' => $reference, 'count'=>$count));
    }


}
