<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Umbrella\Pms\Delivery;

use Doctrine\ORM\EntityManager;
use Umbrella\Pms\Api\IAcknowledge;
use Umbrella\Pms\Api\IMessageListener;
use Umbrella\Pms\Api\IQueue;
use Umbrella\Pms\Queue;

/**
 * @author Italo Lelis de Vietro <italolelis@lellysinformatica.com>
 */
class DocrineDelivery implements IDelivery
{

    protected $em;
    protected $entityClass;
    protected $queue;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getEm()
    {
        return $this->em;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function setEm($em)
    {
        $this->em = $em;
    }

    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    public function send(IQueue $queue, IMessageListener $messageListener)
    {
        foreach ($queue as $item) {
            $messageListener->onMessage($item);
            $this->em->persist($item);
        }

        $this->em->flush();
        return $this;
    }

    public function retrieve($topicName)
    {
        $results = $this->em->getRepository($this->entityClass)->findBy(array(
            'name' => $topicName
        ));
        $queue = new Queue();
        $queue->enqueueMultiple($results);
        return $this->queue = $queue;
    }

    protected function remove($results)
    {
        foreach ($results as $result) {
            if ($result->getAcknowledge() === IAcknowledge::OK) {
                $this->em->remove($result);
            }
        }
    }

    public function __destruct()
    {
        $this->remove($this->queue);
    }

}
