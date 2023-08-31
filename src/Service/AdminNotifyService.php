<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AdminNotifyService
{
    private $manager;
    private $mailer;
    private $twig;
    private $fromAddress;
    private $rootDir;

    public function __construct(EntityManagerInterface $objectManager, MailerInterface $mailer, $fromAddress, Environment $twig, $rootDir)
    {
        $this->mailer = $mailer;
        $this->manager = $objectManager;
        $this->twig = $twig;
        $this->rootDir = $rootDir;
        $this->fromAddress = $fromAddress;
    }

    private function contactList() {
        /** @var User[] $admins */
        $admins = $this->manager->getRepository(User::class)->findByRole("ROLE_ADMIN");
        $to = [];
        foreach ($admins as $admin) {
            if ($admin->isNotifications()) {
                $to[] = $admin->getEmail();
            }
        }
        return $to;
    }
    public function sendAll($title, $content) {
        $this->sendMessage($this->contactList(),$title, $content);
    }


    public function newFeedback($reference, $feedback) {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to(...$this->contactList())
            ->subject("JaCoW Reference Search - Feedback Item")
            ->embedFromPath($this->rootDir. "/public/images/jacow_image.png", "logo")
            ->html($this->twig->render(
                'email/feedback.html.twig', array(
                    'logoID' => "cid:logo",
                    'reference' => $reference,
                    'feedback' => $feedback
                )
            ));
        $this->mailer->send($email);
    }

    public function sendMessage($to, $title, $content) {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to(...$to)
            ->subject("JaCoW Reference Search - Admin Notification")
            ->embedFromPath($this->rootDir. "/public/images/jacow_image.png", "logo")
            ->html($this->twig->render(
                'email/email.html.twig', array(
                    'logoID' => "cid:logo",
                    'title' => $title,
                    'content' => $content
                )
            ));
        $this->mailer->send($email);
    }
}
