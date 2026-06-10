<?php

namespace Diversworld\ContaoEditorialWorkflow\NotificationCenter;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class WorkflowNotificationType implements NotificationTypeInterface
{
    public function __construct(
        private readonly TokenDefinitionFactoryInterface $factory,
        private readonly string                          $name,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTokenDefinitions(): array
    {
        return [
            $this->factory->create(TextTokenDefinition::class, 'table', 'notification_center.token.table'),
            $this->factory->create(TextTokenDefinition::class, 'record_id', 'notification_center.token.record_id'),
            $this->factory->create(TextTokenDefinition::class, 'from_status', 'notification_center.token.from_status'),
            $this->factory->create(TextTokenDefinition::class, 'to_status', 'notification_center.token.to_status'),
            $this->factory->create(TextTokenDefinition::class, 'comment', 'notification_center.token.comment'),
            $this->factory->create(TextTokenDefinition::class, 'user_id', 'notification_center.token.user_id'),
            $this->factory->create(TextTokenDefinition::class, 'user_name', 'notification_center.token.user_name'),
            $this->factory->create(TextTokenDefinition::class, 'author_name', 'notification_center.token.author_name'),
            $this->factory->create(TextTokenDefinition::class, 'record_label', 'notification_center.token.record_label'),
            $this->factory->create(EmailTokenDefinition::class, 'admin_email', 'notification_center.token.admin_email'),
            $this->factory->create(AnythingTokenDefinition::class, 'record_*', 'notification_center.token.record_details'),
        ];
    }
}
