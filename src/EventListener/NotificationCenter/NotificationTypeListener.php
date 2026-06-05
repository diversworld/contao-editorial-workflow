<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\EventListener\NotificationCenter;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Terminal42\NotificationCenterBundle\Event\GetTokenDefinitionsForNotificationTypeEvent;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class NotificationTypeListener
{
    #[AsCallback(table: 'tl_nc_notification', target: 'config.notificationTypes')]
    public function onGetNotificationTypes(array $types): array
    {
        $types['editorial_workflow'] = [
            'editorial_workflow_review',
            'editorial_workflow_approved',
            'editorial_workflow_rejected',
            'editorial_workflow_published',
        ];

        return $types;
    }

    public function __invoke(GetTokenDefinitionsForNotificationTypeEvent $event): void
    {
        $type = $event->getNotificationType()->getName();

        if (!str_starts_with($type, 'editorial_workflow_')) {
            return;
        }

        $event->addTokenDefinition(new TextTokenDefinition('workflow_table', 'editorial_workflow'));
        $event->addTokenDefinition(new TextTokenDefinition('workflow_id', 'editorial_workflow'));
        $event->addTokenDefinition(new TextTokenDefinition('workflow_status', 'editorial_workflow'));
        $event->addTokenDefinition(new TextTokenDefinition('workflow_comment', 'editorial_workflow'));
        $event->addTokenDefinition(new TextTokenDefinition('workflow_user', 'editorial_workflow'));

        // We could add more specific tokens here if needed
    }
}
