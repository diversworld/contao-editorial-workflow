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

namespace Diversworld\ContaoEditorialWorkflow\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Diversworld\ContaoEditorialWorkflow\DiversworldContaoEditorialWorkflow;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(DiversworldContaoEditorialWorkflow::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    'contao-news-bundle',
                    'contao-calendar-bundle',
                    'contao-faq-bundle',
                    'contao-newsletter-bundle',
                    'notification-center',
                ]),
        ];
    }
}
