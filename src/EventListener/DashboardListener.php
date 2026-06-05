<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Diversworld\ContaoEditorialWorkflow\Dashboard\ApprovalDashboard;

class DashboardListener
{
    public function __construct(private readonly ApprovalDashboard $approvalDashboard)
    {
    }

    #[AsHook('parse_backend_template')]
    public function onParseBackendTemplate(string $buffer, string $template): string
    {
        if ($template !== 'be_welcome') {
            return $buffer;
        }

        $this->approvalDashboard->handleApprovalRequest();
        $html = $this->approvalDashboard->render(true);

        if ($html === '') {
            return $buffer;
        }

        return preg_replace('~</div>\s*$~', $html . '</div>', $buffer, 1) ?: $buffer . $html;
    }
}
