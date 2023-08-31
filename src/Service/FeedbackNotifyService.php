<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class FeedbackNotifyService
{
    private $mailer;
    private $twig;
    private $fromAddress;
    private $rootDir;

    public function __construct(MailerInterface $mailer, $fromAddress, Environment $twig, $rootDir)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->rootDir = $rootDir;
        $this->fromAddress = $fromAddress;
    }

    public function receipt($reference, $feedback) {
        if (!$feedback->getEmail()) {
            return;
        }
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($feedback->getEmail())
            ->subject("JaCoW Reference Search - Feedback Received")
            ->embedFromPath($this->rootDir. "/public/images/jacow_image.png", "logo")
            ->html($this->twig->render(
                'email/receipt.html.twig', array(
                    'logoID' => "cid:logo",
                    'reference' => $reference,
                    'feedback' => $feedback
                )
            ));
        $this->mailer->send($email);
    }
}
