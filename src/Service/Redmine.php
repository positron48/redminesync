<?php


namespace App\Service;


use App\Entity\RedmineUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class Redmine
{
    protected $entityManager;
    protected $url;
    protected $externalUrl;
    protected $security;
    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerBagInterface $containerBag,
        Security $security,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->url = $containerBag->get("redmine_url");
        $this->externalUrl = $containerBag->get("external_redmine_url");
        $this->security = $security;
        $this->logger = $logger;
    }

    public function getUserData($username, $password, $primaryRedmine = true)
    {
        $redmine = new \Redmine\Client($primaryRedmine ? $this->url : $this->externalUrl, $username, $password);
        return $redmine->user->getCurrentUser();
    }

    public function getDictionaries($projectId = null)
    {
        $redmine = new \Redmine\Client($this->url, $this->security->getUser()->getToken());
        $projects = $this->getProjects($redmine);
        $trackers = $this->getTrackers($redmine);
        $statuses = $this->getStatuses($redmine);
        $employers = $this->getEmployers($projectId ?: $projects['Multisite'], $redmine);

        return compact('projects', 'trackers', 'statuses', 'employers');
    }

    public function getExernalIssueData($issueId)
    {
        $redmine = new \Redmine\Client($this->externalUrl, $this->security->getUser()->getExternalRedmineToken());
        $issue = $redmine->issue->show($issueId, [
            'include' => ['attachments']
        ]);
        $issue = isset($issue['issue']) ? $issue['issue'] : null;
        if($issue) {
            $issue['url'] = $this->externalUrl . "/issues/" . $issueId;
        }
        return $issue;
    }

    public function createIssue($issue)
    {
        $redmine = new \Redmine\Client($this->url, $this->security->getUser()->getToken());

        $uploads = [];
        if(isset($issue['attachments'])) {
            foreach ($issue['attachments'] as $attachment) {
                $item = [
                    'filename' => $attachment['filename'],
                    'content_type' => $attachment['content_type'],
                    'description' => $attachment['description']
                ];

                $attachment['content_url'] .= '?key=' . $this->security->getUser()->getExternalRedmineToken();
                $upload = $redmine->attachment->upload(
                    file_get_contents($attachment['content_url'])
                );
                $this->logger->info('upload file request', [$item]);
                $this->logger->info('upload file result', [$upload]);

                if ($upload[0] === '{') {
                    $upload = json_decode($upload, true);
                }

                if (isset($upload['upload'])) {
                    $item['token'] = $upload['upload']['token'];
                    $uploads[] = $item;
                }
            }

            if(count($uploads)) {
                $issue['uploads'] = $uploads;
            }
            unset($issue['attachments']);
        }

        $newIssue = $redmine->issue->create($issue);
        $this->logger->info('create issue request', [$issue]);
        $this->logger->info('create issue result', [$newIssue->asXML()]);


        $con = json_encode($newIssue);
        $newIssue = json_decode($con, true);

        if(isset($newIssue['id'])) {
            $newIssue['url'] = $this->url . "/issues/" . $newIssue['id'];
        }
        return $newIssue ?? [];
    }

    /**
     * @param \Redmine\Client $redmine
     * @return array
     */
    protected function getProjects(\Redmine\Client $redmine): array
    {
        $data = $redmine->project->all(['limit' => 200]);
        $data = array_combine(
            array_column($data['projects'], 'name'),
            array_column($data['projects'], 'id')
        );
        ksort($data);
        return $data;
    }

    /**
     * @param \Redmine\Client $redmine
     * @return array
     */
    protected function getTrackers(\Redmine\Client $redmine): array
    {
        $data = $redmine->tracker->all();
        $data = array_combine(
            array_column($data['trackers'], 'name'),
            array_column($data['trackers'], 'id')
        );
        ksort($data);
        return $data;
    }

    /**
     * @param \Redmine\Client $redmine
     * @return array
     */
    protected function getStatuses(\Redmine\Client $redmine): array
    {
        $data = $redmine->issue_status->all();
        $data = array_combine(
            array_column($data['issue_statuses'], 'name'),
            array_column($data['issue_statuses'], 'id')
        );
        ksort($data);
        return $data;
    }

    /**
     * @param \Redmine\Client $redmine
     * @return array
     */
    public function getEmployers($projectId, ?\Redmine\Client $redmine = null): array
    {
        if($redmine === null) {
            $redmine = new \Redmine\Client($this->url, $this->security->getUser()->getToken());
        }
        $data = $redmine->membership->all($projectId);
        foreach ($data['memberships'] as $membership) {
            $result[$membership['user']['name']] = $membership['user']['id'];
        }
        ksort($result);
        return $result;
    }
}