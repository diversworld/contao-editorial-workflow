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
use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\FaqBundle\ContaoFaqBundle;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\NewsletterBundle\ContaoNewsletterBundle;
use Diversworld\ContaoEditorialWorkflow\DiversworldContaoEditorialWorkflow;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\NotificationCenterBundle\Terminal42NotificationCenterBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        $loadAfter = [
            ContaoCoreBundle::class,
            ContaoNewsBundle::class,
            ContaoCalendarBundle::class,
            ContaoFaqBundle::class,
            ContaoNewsletterBundle::class,
        ];

        if (class_exists(Terminal42NotificationCenterBundle::class)) {
            $loadAfter[] = Terminal42NotificationCenterBundle::class;
        }

        return [
            BundleConfig::create(DiversworldContaoEditorialWorkflow::class)
                ->setLoadAfter($loadAfter),
        ];
    }
}
