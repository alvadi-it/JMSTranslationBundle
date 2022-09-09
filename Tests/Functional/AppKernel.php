<?php

declare(strict_types=1);

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\TranslationBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\JMSTranslationBundle;
use JMS\TranslationBundle\Tests\Functional\Fixture\TestBundle\TestBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private mixed $config;

    public function __construct($config)
    {
        parent::__construct('test', true);

        $fs = new Filesystem();
        if (! $fs->isAbsolutePath($config)) {
            $config = __DIR__ . '/config/' . $config;
        }

        if (! file_exists($config)) {
            throw new RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SensioFrameworkExtraBundle(),
            new DoctrineBundle(),
            new TwigBundle(),
            new JMSTranslationBundle(),
            new TestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->config);
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir() . '/cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir() . '/logs';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    private function getBaseDir(): string
    {
        return sys_get_temp_dir() . '/JMSTranslationBundle';
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($config): void
    {
        $this->__construct($config);
    }
}
