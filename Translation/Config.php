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

namespace JMS\TranslationBundle\Translation;

use JMS\TranslationBundle\Exception\InvalidArgumentException;
use JMS\TranslationBundle\Exception\RuntimeException;

/**
 * Configuration.
 *
 * This class contains all configuration for the Updater.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class Config
{
    /**
     * @var string
     */
    private string $translationsDir;

    /**
     * @var string
     */
    private string $locale;

    /**
     * @var array
     */
    private array $ignoredDomains;

    /**
     * @var array
     */
    private array $domains;

    private ?string $outputFormat = null;

    /**
     * @var string
     */
    private string $defaultOutputFormat;

    /**
     * @var bool
     */
    private bool $useIcuMessageFormat;

    /**
     * @var array
     */
    private array $scanDirs;

    /**
     * @var array
     */
    private array $excludedDirs;

    /**
     * @var array
     */
    private array $excludedNames;

    /**
     * @var array
     */
    private array $enabledExtractors;

    /**
     * @var bool
     */
    private bool $keepOldMessages;

    /**
     * @var array
     */
    private array $loadResources;

    public function __construct(string $translationsDir, string $locale, array $ignoredDomains, array $domains, ?string $outputFormat, string $defaultOutputFormat, bool $useIcuMessageFormat, array $scanDirs, array $excludedDirs, array $excludedNames, array $enabledExtractors, bool $keepOldMessages, array $loadResources)
    {
        if (empty($translationsDir)) {
            throw new InvalidArgumentException('The directory where translations are must be set.');
        }

        if (!is_dir($translationsDir) && !mkdir($translationsDir, 0777, true) && !is_dir($translationsDir)) {
            throw new RuntimeException(sprintf('The translations directory "%s" could not be created.', $translationsDir));
        }

        if (empty($scanDirs)) {
            throw new InvalidArgumentException('You must pass at least one directory which should be scanned.');
        }

        foreach ($scanDirs as $k => $dir) {
            if (!is_dir($dir)) {
                throw new RuntimeException(sprintf('The scan directory "%s" does not exist.', $dir));
            }

            $scanDirs[$k] = rtrim($dir, '\\/');
        }

        if (empty($locale)) {
            throw new InvalidArgumentException('The locale cannot be empty.');
        }

        $this->translationsDir = rtrim($translationsDir, '\\/');
        $this->ignoredDomains = $ignoredDomains;
        $this->domains = $domains;
        $this->outputFormat = $outputFormat;
        $this->defaultOutputFormat = $defaultOutputFormat;
        $this->useIcuMessageFormat = $useIcuMessageFormat;
        $this->locale = $locale;
        $this->scanDirs = $scanDirs;
        $this->excludedDirs = $excludedDirs;
        $this->excludedNames = $excludedNames;
        $this->enabledExtractors = $enabledExtractors;
        $this->keepOldMessages = $keepOldMessages;
        $this->loadResources = $loadResources;
    }

    /**
     * @return string
     */
    public function getTranslationsDir(): string
    {
        return $this->translationsDir;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function isIgnoredDomain(string $domain): bool
    {
        return isset($this->ignoredDomains[$domain]);
    }

    /**
     * @return array
     */
    public function getIgnoredDomains(): array
    {
        return $this->ignoredDomains;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function hasDomain($domain): bool
    {
        return isset($this->domains[$domain]);
    }

    /**
     * @return bool
     */
    public function hasDomains(): bool
    {
        return count($this->domains) > 0;
    }

    /**
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getOutputFormat(): ?string
    {
        return $this->outputFormat;
    }

    /**
     * @return string
     */
    public function getDefaultOutputFormat(): string
    {
        return $this->defaultOutputFormat;
    }

    /**
     * @return bool
     */
    public function shouldUseIcuMessageFormat(): bool
    {
        return $this->useIcuMessageFormat;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array
     */
    public function getScanDirs(): array
    {
        return $this->scanDirs;
    }

    /**
     * @return array
     */
    public function getExcludedDirs(): array
    {
        return $this->excludedDirs;
    }

    /**
     * @return array
     */
    public function getExcludedNames(): array
    {
        return $this->excludedNames;
    }

    /**
     * @return array
     */
    public function getEnabledExtractors(): array
    {
        return $this->enabledExtractors;
    }

    /**
     * @return bool
     */
    public function isKeepOldMessages(): bool
    {
        return $this->keepOldMessages;
    }

    /**
     * @return array
     */
    public function getLoadResources(): array
    {
        return $this->loadResources;
    }
}
