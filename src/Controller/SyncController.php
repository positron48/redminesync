<?php

namespace App\Controller;

use App\Entity\RedmineUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        /** @var RedmineUser $user */
        $user = $this->getUser();
        if(!$user){
            return $this->redirectToRoute('login');
        }

        if(!$user->getExternalRedmineToken()){
            return $this->redirectToRoute('settings');
        }

        return $this->render('sync/index.html.twig', [
            'controller_name' => 'SyncController',
        ]);
    }
}
