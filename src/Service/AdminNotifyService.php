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

    public function sendAll($title, $content) {
        /** @var User[] $admins */
        $admins = $this->manager->getRepository(User::class)->findByRole("ROLE_ADMIN");
        foreach ($admins as $admin) {
            $this->sendMessage($admin->getEmail(),$title, $content);
        }
    }

    public function sendMessage($to, $title, $content) {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($to)
            ->subject("JaCoW Reference Search - Admin Notification")
            ->embedFromPath($this->rootDir. "/../web/images/jacow_image.png", "logo")
            ->html($this->twig->render(
                'email/email.html.twig', array(
                    'logoID' => "cid:logo",
                    'title' => $title,
                    'content' => $content
                )
            ));
        try {
            $this->mailer->send($email);
        } catch(Exception $exception) {

        }
    }
}
