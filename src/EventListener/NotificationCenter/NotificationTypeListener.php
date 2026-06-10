<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\EventListener\NotificationCenter;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

class NotificationTypeListener
{
    private const TYPES = [
        'workflow_status_change',
        'workflow_review_requested',
        'workflow_approved',
        'workflow_rejected',
        'workflow_published',
    ];

    #[AsCallback(table: 'tl_nc_notification', target: 'config.notificationTypes')]
    public function onGetNotificationTypes(array $types): array
    {
        $types['editorial_workflow'] = self::TYPES;

        return $types;
    }
}
