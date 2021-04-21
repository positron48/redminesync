<?php

namespace App\Controller;

use App\Entity\RedmineUser;
use App\Form\SettingsType;
use App\Service\Redmine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings", name="settings")
     */
    public function index(Request $request, EntityManagerInterface $entityManager, Redmine $redmine, ContainerBagInterface $containerBag): Response
    {
        /** @var RedmineUser $user */
        $user = $this->getUser();
        if(!$user){
            return $this->redirectToRoute('login');
        }

        $settings = [
            'login' => '',
            'password' => '',
        ];
        $form = $this->createForm(SettingsType::class, $settings);

        $form->handleRequest($request);

        $error = '';
        if ($form->isSubmitted() && $form->isValid()) {
            $settings = $form->getData();
            $userData = $redmine->getUserData($settings['login'], $settings['password'], false);
            if (isset($userData['user'])) {
                /** @var RedmineUser $user */
                $user->setExternalRedmineToken($userData['user']['api_key']);
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('index');
            }
            $error = 'Invalid credentials';
        }

        return $this->render('settings/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'error' => $error,
            'externalRedmineUrl' => $containerBag->get("external_redmine_url")
        ]);
    }
}
