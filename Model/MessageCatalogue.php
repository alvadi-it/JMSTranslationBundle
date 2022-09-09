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

namespace JMS\TranslationBundle\Model;

use JMS\TranslationBundle\Exception\InvalidArgumentException;

/**
 * Represents a collection of **extracted** messages.
 *
 * A catalogue may consist of multiple domains. Each message belongs to
 * a specific domain, and the ID of the message is uniquely identifying the
 * message in its domain, but **not** across domains.
 *
 * This catalogue is only used for extraction, for translation at run-time
 * we still use the optimized catalogue from the Translation component.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MessageCatalogue
{
    private ?string $locale = null;

    /**
     * @var array
     */
    private array $domains = [];

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function add(Message $message): void
    {
        $this
            ->getOrCreateDomain($message->getDomain())
            ->add($message);
    }

    public function set(Message $message, $force = false): void
    {
        $this
            ->getOrCreateDomain($message->getDomain())
            ->set($message, $force);
    }

    /**
     * @param string $id
     * @param string $domain
     *
     * @return Message
     *
     * @throws InvalidArgumentException
     */
    public function get(string $id, string $domain = 'messages'): Message
    {
        return $this->getDomain($domain)->get($id);
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function has(Message $message): bool
    {
        if (!$this->hasDomain($message->getDomain())) {
            return false;
        }

        return $this->getDomain($message->getDomain())->has($message->getId());
    }

    public function merge(MessageCatalogue $catalogue): void
    {
        foreach ($catalogue->getDomains() as $name => $domainCatalogue) {
            $this->getOrCreateDomain($name)->merge($domainCatalogue);
        }
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public function hasDomain(string $domain): bool
    {
        return isset($this->domains[$domain]);
    }

    /**
     * @param string $domain
     *
     * @return MessageCollection
     */
    public function getDomain(string $domain): MessageCollection
    {
        if (!$this->hasDomain($domain)) {
            throw new InvalidArgumentException(sprintf('There is no domain with name "%s".', $domain));
        }

        return $this->domains[$domain];
    }

    /**
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * @param string $domain
     *
     * @return MessageCollection
     */
    private function getOrCreateDomain(string $domain): MessageCollection
    {
        if (!$this->hasDomain($domain)) {
            $this->domains[$domain] = new MessageCollection($this);
        }

        return $this->domains[$domain];
    }
}
