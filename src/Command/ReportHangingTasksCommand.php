<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ReportHangingTasksCommand extends Command
{
    protected static $defaultName = 'app:report:hanging-tasks';
    protected static $defaultDescription = 'Report hanging intaro-inventive multisite tasks';

    /** @var ContainerBagInterface */
    protected $containerBag;

    public function __construct(ContainerBagInterface $containerBag)
    {
        parent::__construct();
        $this->containerBag = $containerBag;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $intaroRedmineURLBase = $this->containerBag->get("redmine_url");
        $intaroRedmineURLTask = $intaroRedmineURLBase . '/issues/';
        $intaroRedmineURLMult = $intaroRedmineURLBase . '/projects/multisite';

        $inventiveRedmineURL = $this->containerBag->get("external_redmine_url") . '/projects/mul';

        $intaroRedmineApiKey = $this->containerBag->get("redmine_report_key");
        $inventiveRedmineApiKey = $this->containerBag->get("external_redmine_report_key");

        $projectID = 'MULTISITE';
        $limitTasks = 1000;

        $intaroManagers = [
            //1808 => ['INVENTIVE_ID' => 426, 'COLOR' => 'Olive', 'NAME' => 'Египко Владимир'], // Египко Владимир
            1986 => ['INVENTIVE_ID' => 632, 'COLOR' => 'Goldenrod', 'NAME' => 'Курасова Маргарита'], // Курасова Маргорита
            2081 => ['INVENTIVE_ID' => 672, 'COLOR' => 'FireBrick', 'NAME' => 'Гвоздецкий Михаил'], // Гвоздецкий Михаил
            2244 => ['INVENTIVE_ID' => 834, 'COLOR' => 'forestgreen', 'NAME' => 'Жидков Эдуард'], // Жидков Эдуард
        ];

        $intaroClient = new \Redmine\Client($intaroRedmineURLMult, $intaroRedmineApiKey);
        $inventiveClient = new \Redmine\Client($inventiveRedmineURL, $inventiveRedmineApiKey);

        $intaroIssues = [];
        foreach (array_keys($intaroManagers) as $userId) {
            $intaroIssues = array_merge($intaroIssues,
                $intaroClient->issue->all([
                    'project_id' => $projectID,
                    'assigned_to_id' => $userId,
                    'limit' => $limitTasks
                ])['issues']
            );
        }

        $inventiveIssues = $inventiveClient->issue->all([
            'project_id' => $projectID,
            'limit' => $limitTasks
        ])['issues'];


        $intaroTasks = array();

        $linkRegExp = '/http:\/\/redmine\.dev\.inventive\.ru\/issues\/[0-9]+/iu';
        $inventiveIdRegExp = '/[0-9]+/m';

        foreach ($intaroIssues as $item) {
            // Получение последней ссылки
            preg_match_all($linkRegExp, $item['description'], $match);
            $match = $match[0];
            if(isset($match[0])) {
                $link = $match[0];

                // Получение id задания из inventive
                preg_match($inventiveIdRegExp, $link, $inventiveId);
            } else {
                $inventiveId = [null];
            }
            //Отсчение пустых задач
            if ($item['id'] !== null) {
                $tmp['id'] = $item['id'];
                $tmp['inventive_id'] = $inventiveId[0];
                $tmp['status_name'] = $item['status']['name'];
                $tmp['subject'] = $item['subject'];
                $tmp['link'] = $link;
                $tmp['color'] = $intaroManagers[$item['assigned_to']['id']]['COLOR'];
                $intaroTasks[] = $tmp;
            }
        }

        //Формирование списка задач

        // Основное тело письма
        $message = '';

        // Счетчик задач
        $i = 1;

        $arTaskTest = array();
        $arClosedTask = array();
        $arWithoutLink = array();

        foreach ($inventiveIssues as $item) {
            foreach ($intaroTasks as $task) {
                // Проверка задач из редмайна inventive на совпадение id из ссылки редмайна intaro
                // Отсчение пустых задач
                if ($task['inventive_id'] == $item['id'] && $item['id'] !== null) {
                    if (in_array($item['assigned_to']['id'], array_column($intaroManagers, 'INVENTIVE_ID'))) {

                        $message .= "<tr style='padding: 1px;'><td>" .
                            $i . '. <a style="white-space: pre-line;  color:' . $task['color'] . '" href="' .
                            $intaroRedmineURLTask . $task['id'] . '"> #' .
                            $task['id'] . '</a>' . ' <a style="color:' . $task['color'] . '" href="' .
                            $task['link'] . '">#' .
                            $task['inventive_id'] . '</a>' . "\n" . ' (' .
                            $task['status_name'] . ') ' .
                            $task['subject'] . '</td></tr>';

                        $i++;
                    }

                    // Отбор протестированных задач
                    if ($item['status']['id'] === 34) {
                        $arTaskTest[] = $task;
                    }

                    // Отбор закрытых задач
                    if ($item['status']['id'] === 18) {
                        $arClosedTask[] = $task;
                    }
                }
            }
        }

        foreach ($intaroTasks as $task) {
            // Отбор задач без ссылки
            if ($task['inventive_id'] === null) {
                $arWithoutLink[] = $task;
            }
        }

        // Формирование сообщения
        $message = "<!DOCTYPE html><html lang=\"ru\"><head><title>Подвисшие задачи проекта мультисайт</title></head>
                <body><div><p>Список задач: </p><table style='width: 100%;'>" . $message . "</table></div>";

        $tmpTasks[] = $this->buildBlockMessage($arTaskTest,
            'Задачи, протестированные на бою в redmine inventive:');
        $tmpTasks[] = $this->buildBlockMessage($arClosedTask,
            'Задачи, закрытые в redmine inventive:');
        $tmpTasks[] = $this->buildBlockMessage($arWithoutLink,
            'Задачи, в которых нет ссылки на redmine inventive:');

        foreach ($tmpTasks as $taskItem) {
            $message = $message . $taskItem;
        }

        foreach ($intaroManagers as $manager) {
            $message .= '<div>
            <p style="color: grey">' . $manager['NAME'] . ' - <span style="color:' . $manager['COLOR'] . '">цвет</span></p>
        </div>';
        }


        $message = $message . "</body></html>";


        $address = $this->containerBag->get('report_emails');
        $subject = 'Подвисшие задачи проекта мультисайт';

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= "From: redsync@env.skillum.ru \r\n";
        $headers .= 'Content-type: text/html; utf-8' . "\r\n";

        $mailResult = mail($address, $subject, $message, $headers);

        $output->write('mail send: ' . ($mailResult ? 'yes' : 'no'));
        $output->write($message);

        return Command::SUCCESS;
    }

    // Функция для построения блока сообщения
    protected function buildBlockMessage($arTasks, $header)
    {
        $block = '<span style="color:grey">' . $header . '</span><br>';

        foreach ($arTasks as $task) {
            $block = $block .
                "\n" . '<a style="white-space: pre-line; color:' . $task['color'] . '" href="https://redmine.skillum.ru/issues/' .
                $task['id'] . '"> #' .
                $task['id'] . "</a>, ";
        }

        $block = mb_substr($block, 0, mb_strlen($block) - 2);
        $block = '<p style=" color:grey " >' . $block . '</p>';

        return $block;
    }
}
