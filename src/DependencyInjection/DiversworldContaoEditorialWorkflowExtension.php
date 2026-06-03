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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DiversworldContaoEditorialWorkflowExtension extends Extension
{
    public function getAlias(): string
    {
        return Configuration::ROOT_KEY;
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('parameters.yaml');
        $loader->load('services.yaml');
        // $loader->load('listener.yaml');
        $rootKey = $this->getAlias();

        $container->setParameter($rootKey . '.four_eyes_principle', $config['four_eyes_principle']);
        $container->setParameter($rootKey . '.enabled_tables', $config['enabled_tables']);
    }
}
