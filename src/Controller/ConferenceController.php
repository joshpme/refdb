<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Entity\Search;
use App\Form\BasicSearchType;
use App\Http\CsvResponse;
use App\Service\AdminNotifyService;
use App\Service\ConferenceLoader;
use App\Service\DoiService;
use App\Service\ImportService;
use App\Service\PaperService;
use App\Service\SearchService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Conference controller.
 *
 * @Route("conference")
 */
class ConferenceController extends AbstractController
{
    private $safeRef = "/^((?!\/\/)[a-zA-Z0-9\/._])+$/";


    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/cache/{id}/search", name="conference_cache_search")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cacheSearchAction(Conference $conference, EntityManagerInterface $manager, SearchService $searchService): JsonResponse {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where('r.conference = :conference')
            ->setParameter('conference', $conference)
            ->getQuery()
            ->getResult();

        $updated = 0;
        foreach ($results as $reference) {
            $updated++;
            $searchService->insertOrUpdate($reference);
        }

        $manager->flush();

        return new JsonResponse([
            "updated" => $updated
        ]);
    }

    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/cache/{id}/text", name="conference_cache_text")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cacheTextAction(Conference $conference, EntityManagerInterface $manager): JsonResponse {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where('r.conference = :conference')
            ->setParameter('conference', $conference)
            ->getQuery()
            ->getResult();

        $cleaned = 0;
        foreach ($results as $result) {
            if ($result->getPaperId() == "") {
                $result->setPaperId(null);
            }
            if ($result->getCache() !== $result->__toString()) {
                $result->setCache($result->__toString());
                $cleaned++;
            }
        }

        $manager->flush();

        return new JsonResponse([
            "updated" => $cleaned
        ]);
    }

    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/cache/{id}/paper", name="conference_cache_paper")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cachePaperAction(Conference $conference, EntityManagerInterface $manager, PaperService $paperService): JsonResponse {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where('r.conference = :conference')
            ->andWhere('r.paperUrl IS NOT NULL')
            ->setParameter('conference', $conference)
            ->getQuery()
            ->getResult();

        $updated = 0;
        foreach ($results as $reference) {
            if ($paperService->check($reference)) {
                $updated++;
            }
        }

        $manager->flush();

        return new JsonResponse([
            "updated" => $updated
        ]);
    }


    /**
     * Finds and displays a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/cache/{id}/doi", name="conference_cache_doi")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cacheDoiAction(Conference $conference, EntityManagerInterface $manager): JsonResponse {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 900);

        /** @var Reference[] $results */
        $results = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r")
            ->where('r.conference = :conference')
            ->setParameter('conference', $conference)
            ->getQuery()
            ->getResult();

        $doiService = new DoiService();

        $output = [];
        foreach ($results as $reference) {
            if ($reference->getConference()->isPublished() && (($reference->getInProc() && $reference->getConference()->isUseDoi() && !$reference->isDoiVerified()) ||
                    ($reference->getCustomDoi() !== null && $reference->getCustomDoi() !== "" && !$reference->isDoiVerified()))) {
                $valid = $doiService->check($reference);
                if (!$valid) {
                    $output[] = "Failed on " . $reference->getConference()->getCode() . " " . $reference->getPaperId();
                } else {
                    $reference->setDoiVerified(true);
                    $output[] = "Found DOI for " . $reference->getConference()->getCode() . " " . $reference->getPaperId();
                }
            }
        }
        $manager->flush();

        return new JsonResponse([
            "output" => $output
        ]);
    }


    /**
     * @Route("/parser", name="conference_parser", options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function parserAction(Request $request): JsonResponse
    {
        $content = $request->request->get('content');
        $lines = explode("\n", $content);
        $data = [];
        foreach ($lines as $line) {
            $contents = explode("=", $line, 2);
            if (count($contents) == 2) {
                [$key, $value] = $contents;
                $data[trim($key)] = trim($value);
            }
        }

        return new JsonResponse($data);
    }

    /**
     * Lists all conference entities.
     * @Route("/", name="conference_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Conference::class)
            ->createQueryBuilder("c")->orderBy("c.id", "DESC");

        if ($form->isSubmitted() && $form->isValid()) {
            $terms = mb_strtolower($form->get('terms')->getData());

            $terms = str_replace("’","'",$terms);

            $search->orWhere('LOWER(c.code) LIKE :terms')
                ->orWhere('LOWER(c.name) LIKE :terms')
                ->orWhere('LOWER(c.year) LIKE :terms')
                ->setParameter("terms", $terms . "%")
                ->orWhere('LOWER(c.location) LIKE :location')
                ->setParameter("location", '%' . $terms . "%");
            //
            $abbreviated = str_replace("international", "int.", $terms);
            $abbreviated = str_replace("conference", "conf.", $abbreviated);

            $search
                ->orWhere('LOWER(c.name) LIKE :abbreviated')
                ->setParameter("abbreviated", $abbreviated . "%");

            if (preg_match("/(\d{4})/", $terms, $matches)) {
                foreach ($matches as $match) {
                    $terms = str_replace($match, substr($match,2), $terms);
                }
                $search->orWhere('LOWER(c.code) LIKE :date')
                    ->orWhere('LOWER(c.year) LIKE :date')
                    ->setParameter('date','%' . $terms);
            }
        }

        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('conference/index.html.twig', array('pagination' => $pagination, 'search'=> $form->createView()));
    }

    /**
     * Make this conference my current conference (changes the way the reference appears)
     * @IsGranted("ROLE_ADMIN")
     * @Route("/export/{id}", name="conference_export")
     * @param Request $request
     * @param Conference $conference
     * @return CsvResponse
     */
    public function export(Request $request, Conference $conference, EntityManagerInterface $manager) {
        $references = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->select("r, a, c")
            ->join("r.conference", "c")
            ->leftJoin("r.authors", "a")
            ->where("r.conference = :conference")
            ->setParameter("conference",$conference)
            ->getQuery()
            ->getResult();

        $output = [];

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $item = [];
            $item["paper"] = $reference->getPaperId();
            $authors = [];
            /** @var Author $author */
            foreach ($reference->getAuthors() as $author) {
                $authors[] = $author->getName();
            }
            if (count($authors) == 0) {
                $item["authors"] = $reference->getOriginalAuthors();
            } else {
                $item["authors"] = implode(", ", $authors);
            }

            $item["title"] = $reference->getTitle();
            $item["position"] = $reference->getPosition();
            $item["contribution"] = $reference->getContributionId();
            $item['inproceedings'] = $reference->getInProc() ? "yes" : "no";
            $item["doi"] = $reference->getCustomDoi();
            $item["url"] = $reference->getPaperUrl();

            $output[] = $item;
        }

        return new CsvResponse($output);

    }

    /**
     * Creates a new conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new", name="conference_new")
     */
    public function newAction(Request $request, ConferenceLoader $conferenceLoader)
    {
        $conference = new Conference();
        $source = $request->query->get("source");
        if ($source !== null) {
            $response = $conferenceLoader->load($conference, $source);
            if ($response !== null) {
                $this->addFlash("notice", $response);
            }
        }
        $form = $this->createForm('App\Form\ConferenceType', $conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($conference);
            $em->flush();

            return $this->redirectToRoute('conference_show', array('id' => $conference->getId()));
        }

        return $this->render('conference/new.html.twig', array(
            'conference' => $conference,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a conference entity.
     *
     * @Route("/show/{id}", name="conference_show")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, Conference $conference, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->where("r.conference = :conference")
            ->setParameter("conference", $conference);

        if ($form->isSubmitted() && $form->isValid()) {
            $terms = mb_strtolower($form->get('terms')->getData());
            $search
                ->andWhere('LOWER(r.cache) LIKE :terms')
                ->setParameter("terms", '%' . $terms . "%");
        }


        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('conference/show.html.twig', array(
            'conference' => $conference,
            'pagination' => $pagination,
            "search"=>$form->createView()));

    }

    /**
     * Displays a form to edit an existing conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="conference_edit")
     */
    public function editAction(Request $request, Conference $conference)
    {
        $deleteForm = $this->createDeleteForm($conference);
        $editForm = $this->createForm('App\Form\ConferenceType', $conference);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash("success", "Conference settings saved!");
            return $this->redirectToRoute('conference_edit', array('id' => $conference->getId()));
        }

        return $this->render('conference/edit.html.twig', array(
            'conference' => $conference,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="conference_delete")
     */
    public function deleteAction(Request $request, Conference $conference)
    {
        $form = $this->createDeleteForm($conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($conference);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("conference_index")]);
        }

        return $this->render("conference/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a conference entity.
     *
     * @param Conference $conference The conference entity
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Conference $conference)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('conference_delete', array('id' => $conference->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/search/{query}/{type}", name="conference_search", options={"expose"=true})
     * @param $query
     * @param string $type
     * @return JsonResponse
     */
    public function searchAction($query, $type = "name") {
        $results = $this->getDoctrine()->getManager()->getRepository(Conference::class)
            ->search($query, $type);
        return new JsonResponse($results);
    }

    /**
     * @Route("/update_all", name="update_all")
     * @param EntityManagerInterface $manager
     * @param AdminNotifyService $adminNotificationService
     * @param ImportService $importService
     * @return Response
     */
    public function updateConference(EntityManagerInterface $manager, AdminNotifyService $adminNotificationService, ImportService $importService) {
        $conferences = $manager
            ->getRepository(Conference::class)
            ->createQueryBuilder("c")
            ->andWhere("c.isPublished = false")
            ->getQuery()->getResult();

        $output = "";
        /** @var Conference $conference */
        foreach ($conferences as $conference) {
            if ($conference->getImportUrl() !== null && $conference->getConferenceEnd() > new DateTime()) {
                $output .= "Re-importing " . $conference . "\n";
                try {
                    $written = $importService->merge($conference->getImportUrl(), $conference);
                    $output .= $written . " references created" . "\n";
                } catch (\Exception $exception) {
                    $message = " Failed updating " . $conference . "\n\n " . $exception->getMessage();
                    $adminNotificationService->sendAll("Automatic Import Failed", $message);
                    $output .= $message . "\n";

                    return new Response($output, 500);
                }
            }
        }
        return new Response($output);
    }
}
