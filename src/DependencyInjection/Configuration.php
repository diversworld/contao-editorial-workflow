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

namespace Diversworld\ContaoEditorialWorkflow\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_KEY = 'diversworld_contao_editorial_workflow';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('four_eyes_principle')
            ->defaultTrue()
            ->end()
            ->arrayNode('enabled_tables')
            ->scalarPrototype()->end()
            ->defaultValue(['tl_page', 'tl_article', 'tl_content', 'tl_news', 'tl_calendar_events', 'tl_faq'])
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
