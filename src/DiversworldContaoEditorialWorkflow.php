<?php

declare(strict_types=1);

/*
 * This file is part of Editorial Workflow.
 *
 * (c) Eckhard Becker <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-editorial-workflow
 */

namespace Diversworld\ContaoEditorialWorkflow;

use Diversworld\ContaoEditorialWorkflow\DependencyInjection\DiversworldContaoEditorialWorkflowExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DiversworldContaoEditorialWorkflow extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DiversworldContaoEditorialWorkflowExtension();
    }
}
