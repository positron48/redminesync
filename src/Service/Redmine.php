<?php


namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class Redmine
{
    protected $entityManager;
    protected $url;

    public function __construct(EntityManagerInterface $entityManager, ContainerBagInterface $containerBag)
    {
        $this->entityManager = $entityManager;
        $this->url = $containerBag->get("redmine_url");
    }

    public function getUserData($username, $password)
    {
        $redmine = new \Redmine\Client($this->url, $username, $password);
        return $redmine->user->getCurrentUser();
    }
}