<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 * User controller.
 *
 * @Route("admin/user")
 */
class UserController extends AbstractController
{
    /**
     * Lists all user entities.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/", name="user_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(User::class)
            ->createQueryBuilder("u")
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('user/index.html.twig', array('pagination' => $pagination));
    }


    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="user_edit")
     */
    public function editAction(Request $request, User $user, EntityManagerInterface $manager)
    {
        $form = $this->createFormBuilder($user)
            ->add("enabled", CheckboxType::class, ["label" => "Enabled", 'required' => false])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'ROLE_USER' => 'ROLE_USER',
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add("notifications", CheckboxType::class, ["label" => "Receive email notifications (admin only)", 'required' => false])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("user_index")]);
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/show/{id}", name="user_show")
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="user_delete")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("user_index")]);
        }

        return $this->render("user/delete.html.twig", array("delete_form"=>$form->createView()));
    }
    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createPromoteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_promote', array('id' => $user->getId())))
            ->getForm()
            ;
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
