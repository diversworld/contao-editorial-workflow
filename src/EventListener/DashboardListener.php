<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;

class DashboardListener
{
    private $db;
    private $requestStack;

    public function __construct(\Doctrine\DBAL\Connection $db, RequestStack $requestStack)
    {
        $this->db = $db;
        $this->requestStack = $requestStack;
    }

    public function onGetDashboard()
    {
        // Hier könnte ein Custom-Dashboard-Widget für Contao 5 implementiert werden
        // Aktuell generieren wir eine Übersicht der ausstehenden Prüfungen

        $pending = $this->db->fetchAllAssociative("
            SELECT id, workflow_status, 'tl_news' as ptable FROM tl_news WHERE workflow_status = 'review'
            UNION
            SELECT id, workflow_status, 'tl_page' as ptable FROM tl_page WHERE workflow_status = 'review'
            -- Weitere Tabellen...
        ");

        return $pending;
    }
}
