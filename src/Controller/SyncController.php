<?php

namespace App\Controller;

use App\Entity\RedmineUser;
use App\Form\CloneIssueType;
use App\Form\SettingsType;
use App\Service\Redmine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request, Redmine $redmine, ContainerBagInterface $containerBag): Response
    {
        /** @var RedmineUser $user */
        $user = $this->getUser();

        if(!$user){
            return $this->redirectToRoute('login');
        }

        if(!$user->getExternalRedmineToken()){
            return $this->redirectToRoute('settings');
        }

        $projectId = null;
        if(is_array($request->get('clone_issue')) && isset($request->get('clone_issue')['project'])){
            $projectId = $request->get('clone_issue')['project'];
        }
        $dictionaries = $redmine->getDictionaries($projectId);
        $form = $this->createForm(CloneIssueType::class, [], $dictionaries);

        $form->handleRequest($request);

        $error = '';
        $issue = null;
        $newIssue = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if(!empty($formData['issue'])) {
                preg_match('#(^|/)(\d+)#', $formData['issue'], $matches);
                $issueId = $matches[2];
                if($issueId > 0) {
                    $issue = $redmine->getExernalIssueData($issueId);
                    if($issue) {
                        if($formData['tracker'] > 0 && $formData['project'] > 0) {
                            $newIssue = [
                                'tracker_id' => $formData['tracker'],
                                'project_id' => $formData['project'],
                                'status_id' => $formData['status'],
                                'subject' => $issue['subject'],
                                'description' => "Задача: " . $issue['url'] . "\r\n\r\n" .
                                    $issue['description']
                            ];
                            if($formData['employer'] > 0){
                                $newIssue['assigned_to_id'] = $formData['employer'];
                            }
                            if($issue['attachments']){
                                $newIssue['attachments'] = $issue['attachments'];
                            }

                            $newIssue = $redmine->createIssue($newIssue);
                            if(isset($newIssue['id'])) {
                                $form = $this->createForm(CloneIssueType::class, [], $dictionaries);
                            }
                        } else {

                            //todo по трекеру/проекту получить сопоставление
                            $formData['tracker'] = isset($dictionaries['trackers']['Разработка']) ?
                                $dictionaries['trackers']['Разработка'] :
                                null;
                            $formData['project'] = isset($dictionaries['projects']['Multisite']) ?
                                $dictionaries['projects']['Multisite'] :
                                null;
                            $formData['status'] = isset($dictionaries['statuses']['Новый']) ?
                                $dictionaries['statuses']['Новый'] :
                                null;
                            $formData['employer'] = null;

                            $form = $this->createForm(CloneIssueType::class, $formData, $dictionaries);
                        }
                    } else {
                        $error = 'Issue ' . $issueId . ' not found';
                    }
                } else {
                    $error = 'Issue id is not found';
                }
            } else {
                $error = 'Issue must be set';
            }
        }

        return $this->render('sync/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'error' => $error,
            'issue' => $issue,
            'newIssue' => $newIssue
        ]);
    }
}
