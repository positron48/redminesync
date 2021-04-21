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

    public function getDictionaries($token)
    {
        $redmine = new \Redmine\Client($this->url, $token);
        $projects = self::getProjects($redmine);
        $trackers = self::getTrackers($redmine);
        $statuses = self::getStatuses($redmine);
        $employers = self::getEmployers($redmine, $projects['Multisite']);

        return compact('projects', 'trackers', 'statuses', 'employers');
    }

    public function getExernalIssueData($issueId, $externalRedmineToken)
    {
        $redmine = new \Redmine\Client($this->externalUrl, $externalRedmineToken);
        $issue = $redmine->issue->show($issueId, [
            'include' => ['attachments']
        ]);
        $issue = isset($issue['issue']) ? $issue['issue'] : null;
        if($issue) {
            $issue['url'] = $this->externalUrl . "/issues/" . $issueId;
        }
        return $issue;
    }

    public function createIssue($issue, $token, $externalRedmineToken)
    {
        $redmine = new \Redmine\Client($this->url, $token);

        $uploads = [];
        if(isset($issue['attachments'])) {
            foreach ($issue['attachments'] as $attachment) {
                $item = [
                    'filename' => $attachment['filename'],
                    'content_type' => $attachment['content_type'],
                    'description' => $attachment['description']
                ];

                $attachment['content_url'] .= '?key=' . $externalRedmineToken;
                $upload = $redmine->attachment->upload(
                    file_get_contents($attachment['content_url'])
                );

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
                unset($issue['attachments']);
            }
        }

        $newIssue = $redmine->issue->create($issue);

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
    protected static function getProjects(\Redmine\Client $redmine): array
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
    protected static function getTrackers(\Redmine\Client $redmine): array
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
    protected static function getStatuses(\Redmine\Client $redmine): array
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
    protected static function getEmployers(\Redmine\Client $redmine, $projectId): array
    {
        $data = $redmine->membership->all($projectId);
        foreach ($data['memberships'] as $membership) {
            $result[$membership['user']['name']] = $membership['user']['id'];
        }
        ksort($result);
        return $result;
    }
}