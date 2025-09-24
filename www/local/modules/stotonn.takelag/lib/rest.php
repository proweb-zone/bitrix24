<?php
namespace Your\Company;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;

class RestHandler extends Controller
{
    // Настройка префикса для методов
    protected function getDefaultPreFilters()
    {
        return [
            new ActionFilter\Authentication(),
            new ActionFilter\HttpMethod(['POST', 'GET']),
            new ActionFilter\Csrf(), // Защита от CSRF
        ];
    }

    /**
     * Ваш кастомный метод API
     * Будет доступен как your.company.resthandler.createtask
     */
    public function createTaskAction($title, $description, $responsibleId)
    {
        // Проверяем авторизацию
        if (!Loader::includeModule('tasks')) {
            $this->addError(new Error('Module tasks not installed'));
            return null;
        }

        // Создаем задачу
        $task = new \CTasks;
        $fields = [
            'TITLE' => $title,
            'DESCRIPTION' => $description,
            'RESPONSIBLE_ID' => $responsibleId,
            'CREATED_BY' => $GLOBALS['USER']->GetID(),
        ];

        $taskId = $task->Add($fields);

        if (!$taskId) {
            $this->addError(new Error($task->GetErrors()));
            return null;
        }

        return ['task_id' => $taskId, 'status' => 'success'];
    }

    /**
     * Еще один метод - получение статистики
     */
    public function getStatsAction($dateFrom, $dateTo)
    {
        // Ваша логика получения статистики
        $stats = [
            'leads_created' => 150,
            'deals_closed' => 45,
            'revenue' => 1250000
        ];

        return $stats;
    }
}
?>
