<?php
namespace App\EventSubscriber;

use App\Entity\Reference;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class ReferenceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Reference) {
            $entity->setCache($entity->__toString());
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Reference) {
            $entity->setCache(substr($entity->__toString(),0,750));
            // Max length 750
        }
    }
}
