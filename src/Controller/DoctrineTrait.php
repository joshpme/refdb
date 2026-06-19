<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Provides access to the Doctrine registry from controllers.
 *
 * Symfony 6 removed AbstractController::getDoctrine(); this trait restores the
 * same helper via autowired setter injection so existing call sites keep working.
 */
trait DoctrineTrait
{
    private ManagerRegistry $doctrine;

    #[Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }
}
