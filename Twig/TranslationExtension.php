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

namespace JMS\TranslationBundle\Twig;

/**
 * Provides some extensions for specifying translation metadata.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    private TranslatorInterface $translator;

    private bool $debug;

    public function __construct(TranslatorInterface $translator, $debug = false)
    {
        $this->translator = $translator;
        $this->debug = $debug;
    }

    /**
     * @return array
     */
    public function getNodeVisitors(): array
    {
        $visitors = [
            new NormalizingNodeVisitor(),
            new RemovingNodeVisitor(),
        ];

        if ($this->debug) {
            $visitors[] = new DefaultApplyingNodeVisitor();
        }

        return $visitors;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('desc', [$this, 'desc']),
            new TwigFilter('meaning', [$this, 'meaning']),
        ];
    }

    /**
     * @param string $message
     * @param string $defaultMessage
     * @param int $count
     * @param array $arguments
     * @param string|null $domain
     * @param string|null $locale
     *
     * @return string
     */
    public function transchoiceWithDefault($message, $defaultMessage, $count, array $arguments = [], $domain = null, $locale = null): string
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        if (false === $this->translator->getCatalogue($locale)->defines($message, $domain)) {
            return $this->doTransChoice($defaultMessage, $count, array_merge(['%count%' => $count], $arguments), $domain, $locale);
        }

        return $this->doTransChoice($message, $count, array_merge(['%count%' => $count], $arguments), $domain, $locale);
    }

    private function doTransChoice($message, $count, array $arguments, $domain, $locale): string
    {
        return $this->translator->trans($message, array_merge(['%count%' => $count], $arguments), $domain, $locale);
    }

    /**
     * @param mixed $v
     *
     * @return mixed
     */
    public function desc($v): mixed
    {
        return $v;
    }

    /**
     * @param mixed $v
     *
     * @return mixed
     */
    public function meaning($v): mixed
    {
        return $v;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'jms_translation';
    }
}
