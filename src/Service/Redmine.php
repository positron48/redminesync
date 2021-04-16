<?php


namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class Redmine
{
    protected $entityManager;
    protected $url;
    protected $externalUrl;

    public function __construct(EntityManagerInterface $entityManager, ContainerBagInterface $containerBag)
    {
        $this->entityManager = $entityManager;
        $this->url = $containerBag->get("redmine_url");
        $this->externalUrl = $containerBag->get("external_redmine_url");
    }

    public function getUserData($username, $password, $primaryRedmine = true)
    {
        $redmine = new \Redmine\Client($primaryRedmine ? $this->url : $this->externalUrl, $username, $password);
        return $redmine->user->getCurrentUser();
    }
}