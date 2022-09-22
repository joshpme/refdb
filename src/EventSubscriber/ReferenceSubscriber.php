<?php
namespace App\EventSubscriber;

use App\Entity\Reference;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
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

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->index($args);
    }

    public function prePersist(PreUpdateEventArgs $args)
    {
        $this->index($args);
    }

    public function index(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Reference) {
            $entity->setCache($entity->__toString());
        }
    }
}
