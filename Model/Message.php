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

use JMS\TranslationBundle\Exception\RuntimeException;

/**
 * Represents an _extracted_ message.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Message
{
    /**
     * Unique ID of this message (same across the same domain).
     *
     * @var string
     */
    private string $id;

    /**
     * @var bool
     */
    private bool $new = true;

    /**
     * @var string
     */
    private string $domain;

    private ?string $localeString = null;

    /**
     * Additional information about the intended meaning.
     *
     * @var string
     */
    private ?string $meaning = null;

    /**
     * The description/sample for translators.
     *
     * @var string
     */
    private ?string $desc = null;

    /**
     * The sources where this message occurs.
     *
     * @var array
     */
    private array $sources = [];

    /**
     * @param string $id
     * @param string $domain
     *
     * @return Message
     *
     * @static
     *@deprecated Will be removed in 2.0. Use the FileSourceFactory
     *
     */
    public static function forThisFile(string $id, string $domain = 'messages'): Message
    {
        $message = new static($id, $domain);

        $trace = debug_backtrace(0);
        if (isset($trace[0]['file'])) {
            $message->addSource(new FileSource($trace[0]['file']));
        }

        return $message;
    }

    /**
     * @param string $id
     * @param string $domain
     *
     * @return static
     *
     * @static
     */
    public static function create(string $id, string $domain = 'messages'): static
    {
        return new static($id, $domain);
    }

    /**
     * @param string $id
     * @param string $domain
     */
    public function __construct(string $id, string $domain = 'messages')
    {
        $this->id = $id;
        $this->domain = $domain;
    }

    /**
     * @param SourceInterface $source
     *
     * @return Message
     */
    public function addSource(SourceInterface $source): static
    {
        if ($this->hasSource($source)) {
            return $this;
        }

        $this->sources[] = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->new;
    }

    /**
     * This will return:
     * 1) the localeString, ie the translated string
     * 2) description (if new)
     * 3) id (if new)
     * 4) empty string.
     *
     * @return string
     */
    public function getLocaleString(): string
    {
        return $this->localeString ?? ($this->new ? ($this->desc ?? $this->id) : '');
    }

    /**
     * Returns the string from which to translate.
     *
     * This typically is the description, but we will fallback to the id
     * if that has not been given.
     *
     * @return string
     */
    public function getSourceString(): string
    {
        return $this->desc ?: $this->id;
    }

    public function getMeaning(): ?string
    {
        return $this->meaning;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function setMeaning(?string $meaning = null): static
    {
        $this->meaning = $meaning;

        return $this;
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function setNew(bool $bool): static
    {
        $this->new = $bool;

        return $this;
    }

    public function setDesc(?string $desc = null): static
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * @param string $str
     *
     * @return $this
     */
    public function setLocaleString(string $str): static
    {
        $this->localeString = $str;

        return $this;
    }

    public function setSources(array $sources = []): static
    {
        $this->sources = $sources;

        return $this;
    }

    /**
     * Return true if we have a translated string. This is not the same as running:
     *   $str = $message->getLocaleString();
     *   $bool = !empty($str);.
     *
     * The $message->getLocaleString() will return a description or an id if the localeString does not exist.
     *
     * @return bool
     */
    public function hasLocaleString(): bool
    {
        return !empty($this->localeString);
    }

    /**
     * Merges an extracted message.
     *
     * Do not use this if you want to merge a message from an existing catalogue.
     * In these cases, use mergeExisting() instead.
     *
     * @param Message $message
     *
     * @throws RuntimeException
     */
    public function merge(Message $message): void
    {
        if ($this->id !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->id, $message->getId()));
        }

        if (null !== $meaning = $message->getMeaning()) {
            $this->meaning = $meaning;
        }

        if (null !== $desc = $message->getDesc()) {
            $this->desc = $desc;
            $this->localeString = null;
            if ($message->hasLocaleString()) {
                $this->localeString = $message->getLocaleString();
            }
        }

        foreach ($message->getSources() as $source) {
            $this->addSource($source);
        }

        $this->setNew($message->isNew());
    }

    /**
     * Merges a message from an existing translation catalogue.
     *
     * Do not use this if you want to merge a message from an extracted catalogue.
     * In these cases, use merge() instead.
     *
     * @deprecated not in use atm
     *
     * @param Message $message
     */
    public function mergeExisting(Message $message): void
    {
        if ($this->id !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->id, $message->getId()));
        }

        if (null !== $meaning = $message->getMeaning()) {
            $this->meaning = $meaning;
        }

        if (null !== $desc = $message->getDesc()) {
            $this->desc = $desc;
        }

        $this->setNew($message->isNew());
        if ($localeString = $message->getLocaleString()) {
            $this->localeString = $localeString;
        }
    }

    /**
     * Merge a scanned message into an extising message.
     *
     * This method does essentially the same as {@link mergeExisting()} but with reversed operands.
     * Whereas {@link mergeExisting()} is used to merge an existing message into a scanned message (this),
     * {@link mergeScanned()} is used to merge a scanned message into an existing message (this).
     * The result of both methods is the same, except that the result will end up in the existing message,
     * instead of the scanned message, so extra information read from the existing message is not discarded.
     *
     * @author Dieter Peeters <peetersdiet@gmail.com>
     *
     * @param Message $message
     */
    public function mergeScanned(Message $message): void
    {
        if ($this->id !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->id, $message->getId()));
        }

        if (null === $this->getMeaning()) {
            $this->meaning = $message->getMeaning();
        }

        if (null === $this->getDesc()) {
            $this->desc = $message->getDesc();
        }

        $this->sources = [];
        foreach ($message->getSources() as $source) {
            $this->addSource($source);
        }

        if (!$this->getLocaleString()) {
            $this->localeString = $message->getLocaleString();
        }
    }

    /**
     * @param SourceInterface $source
     *
     * @return bool
     */
    public function hasSource(SourceInterface $source): bool
    {
        foreach ($this->sources as $cSource) {
            if ($cSource->equals($source)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allows us to use this with existing message catalogues.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }
}
