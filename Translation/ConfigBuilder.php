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

final class ConfigBuilder
{
    /**
     * @var string Path to translation directory
     */
    private string $translationsDir;

    /**
     * @var string
     */
    private string $locale;

    /**
     * @var array
     */
    private array $ignoredDomains = [];

    /**
     * @var array
     */
    private array $domains = [];

    private ?string $outputFormat = null;

    /**
     * @var string
     */
    private string $defaultOutputFormat = 'xlf';

    /**
     * @var bool
     */
    private bool $useIcuMessageFormat = false;

    /**
     * @var array
     */
    private array $scanDirs = [];

    /**
     * @var array
     */
    private array $excludedDirs = ['Tests'];

    /**
     * @var array
     */
    private array $excludedNames = ['*Test.php', '*TestCase.php'];

    /**
     * @var array
     */
    private array $enabledExtractors = [];

    /**
     * @var bool
     */
    private bool $keepOldTranslations = false;

    /**
     * @var array
     */
    private array $loadResources = [];

    /**
     * @param Config $config
     *
     * @return ConfigBuilder
     *
     * @static
     */
    public static function fromConfig(Config $config): ConfigBuilder
    {
        $builder = new self();
        $builder->setTranslationsDir($config->getTranslationsDir());
        $builder->setLocale($config->getLocale());
        $builder->setIgnoredDomains($config->getIgnoredDomains());
        $builder->setDomains($config->getDomains());
        $builder->setOutputFormat($config->getOutputFormat());
        $builder->setDefaultOutputFormat($config->getDefaultOutputFormat());
        $builder->setUseIcuMessageFormat($config->shouldUseIcuMessageFormat());
        $builder->setScanDirs($config->getScanDirs());
        $builder->setExcludedDirs($config->getExcludedDirs());
        $builder->setExcludedNames($config->getExcludedNames());
        $builder->setEnabledExtractors($config->getEnabledExtractors());
        $builder->setLoadResources($config->getLoadResources());

        return $builder;
    }

    /**
     * Sets the default output format.
     *
     * The default output format is used when the following conditions are met:
     *   - there is no existing file for the given domain
     *   - you haven't forced a format
     *
     * @param string $format
     *
     * @return $this
     */
    public function setDefaultOutputFormat(string $format): ConfigBuilder
    {
        $this->defaultOutputFormat = $format;

        return $this;
    }

    /**
     * Sets the output format.
     *
     * This will force all updated domains to be in this format even if input
     * files have a different format. This will also cause input files of
     * another format to be deleted.
     *
     * @param string|null $format
     *
     * @return $this
     */
    public function setOutputFormat(?string $format = null): ConfigBuilder
    {
        $this->outputFormat = $format;

        return $this;
    }

    /**
     * Defines whether or not the ICU message format should be used.
     *
     * If enabled, translation files will be suffixed with +intl-icu, e.g.:
     * message+intl-icu.en.xlf
     *
     * @param bool $useIcuMessageFormat
     *
     * @return $this
     */
    public function setUseIcuMessageFormat(bool $useIcuMessageFormat): ConfigBuilder
    {
        $this->useIcuMessageFormat = $useIcuMessageFormat;

        return $this;
    }

    /**
     * Sets ignored domains.
     *
     * These domains are not altered by the update() command, and also do not
     * appear in the change set calculated by getChangeSet().
     *
     * @param array $domains an array of the form array('domain' => true, 'another_domain' => true)
     *
     * @return $this
     */
    public function setIgnoredDomains(array $domains): ConfigBuilder
    {
        $this->ignoredDomains = $domains;

        return $this;
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function addIgnoredDomain(string $domain): ConfigBuilder
    {
        $this->ignoredDomains[$domain] = true;

        return $this;
    }

    /**
     * @param array $domains
     *
     * @return $this
     */
    public function setDomains(array $domains): ConfigBuilder
    {
        $this->domains = $domains;

        return $this;
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function addDomain(string $domain): ConfigBuilder
    {
        $this->domains[$domain] = true;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale(string $locale): ConfigBuilder
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param string $dir
     *
     * @return $this
     */
    public function setTranslationsDir(string $dir): ConfigBuilder
    {
        $this->translationsDir = $dir;

        return $this;
    }

    /**
     * @param array $dirs
     *
     * @return $this
     */
    public function setScanDirs(array $dirs): ConfigBuilder
    {
        $this->scanDirs = $dirs;

        return $this;
    }

    /**
     * @param array $dirs
     *
     * @return $this
     */
    public function setExcludedDirs(array $dirs): ConfigBuilder
    {
        $this->excludedDirs = $dirs;

        return $this;
    }

    /**
     * @param array $names
     *
     * @return $this
     */
    public function setExcludedNames(array $names): ConfigBuilder
    {
        $this->excludedNames = $names;

        return $this;
    }

    /**
     * @param array $aliases
     *
     * @return $this
     */
    public function setEnabledExtractors(array $aliases): ConfigBuilder
    {
        $this->enabledExtractors = $aliases;

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function enableExtractor(string $alias): ConfigBuilder
    {
        $this->enabledExtractors[$alias] = true;

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function disableExtractor(string $alias): ConfigBuilder
    {
        unset($this->enabledExtractors[$alias]);

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setKeepOldTranslations(bool $value): ConfigBuilder
    {
        $this->keepOldTranslations = $value;

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return new Config(
            $this->translationsDir,
            $this->locale,
            $this->ignoredDomains,
            $this->domains,
            $this->outputFormat,
            $this->defaultOutputFormat,
            $this->useIcuMessageFormat,
            $this->scanDirs,
            $this->excludedDirs,
            $this->excludedNames,
            $this->enabledExtractors,
            $this->keepOldTranslations,
            $this->loadResources
        );
    }

    /**
     * @param array $loadResources
     *
     * @return $this
     */
    public function setLoadResources(array $loadResources): ConfigBuilder
    {
        $this->loadResources = $loadResources;

        return $this;
    }
}
