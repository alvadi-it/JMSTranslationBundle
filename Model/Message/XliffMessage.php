<?php

declare(strict_types=1);

/*
 * Copyright 2013 Dieter Peeters <peetersdiet@gmail.com>
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

namespace JMS\TranslationBundle\Model\Message;

use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Model\Message;

/**
 * Represents an _existing_ message from an XLIFF-file.
 *
 * Currently supports preservation of:
 * - note-elements (as child of trans-unit element)
 * - attribute trans-unit['approved']
 * - attribute target['state']
 *
 * @author Dieter Peeters <peetersdiet@gmail.com>
 *
 * @see http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html
 */
class XliffMessage extends Message
{
    protected static array $states = [];
    public const STATE_NONE = null;
    public const STATE_FINAL = 'final';
    public const STATE_NEEDS_ADAPTATION = 'needs-adaptation';
    public const STATE_NEEDS_L10N = 'needs-l10n';
    public const STATE_NEEDS_REVIEW_ADAPTATION = 'needs-review-adaptation';
    public const STATE_NEEDS_REVIEW_L10N = 'needs-review-l10n';
    public const STATE_NEEDS_REVIEW_TRANSLATION = 'needs-review-translation';
    public const STATE_NEEDS_TRANSLATION = 'needs-translation';
    public const STATE_NEW = 'new';
    public const STATE_SIGNED_OFF = 'signed-off';
    public const STATE_TRANSLATED = 'translated';

    protected bool $approved = false;
    protected ?string $state;
    protected array $notes = [];

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * @param bool $approved
     *
     * @return $this
     */
    public function setApproved(bool $approved): static
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasState(): bool
    {
        return isset($this->state);
    }

    /**
     * @param string|null $state
     *
     * @return $this
     */
    public function setState(string $state = null): static
    {
        $this->state = $state;
        parent::setNew($this->isNew());

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->state === XliffMessageState::STATE_NEW;
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function setNew(bool $bool): static
    {
        if ($bool) {
            $this->state = XliffMessageState::STATE_NEW;
        } elseif ($this->isNew()) {
            // $bool==false => leave state untouched unless it is set to STATE_NEW
            $this->state = null;
        }

        return parent::setNew($bool);
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return !($this->isApproved() || ($this->hasState() && $this->state !== XliffMessageState::STATE_NEW));
    }

    /**
     * @return bool
     */
    public function hasNotes(): bool
    {
        return !empty($this->notes);
    }

    /**
     * @return array
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    /**
     * @param string $text
     * @param null $from
     *
     * @return $this
     */
    public function addNote(string $text, $from = null): static
    {
        $note = [
            'text' => $text,
        ];
        if (isset($from)) {
            $note['from'] = (string) $from;
        }
        $this->notes[] = $note;

        return $this;
    }

    /**
     * @param array $notes
     *
     * @return $this
     */
    public function setNotes(array $notes = []): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($id, $domain = 'messages')
    {
        parent::__construct($id, $domain);
        $this->state = parent::isNew() ? XliffMessageState::STATE_NEW : null; // sync with the parent's new-attribute
    }

    /**
     * {@inheritdoc}
     */
    public function merge(Message $message): void
    {
        if ($this->getId() !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->getId(), $message->getId()));
        }

        foreach ($message->getSources() as $source) {
            $this->addSource($source);
        }

        $oldDesc = $this->getDesc();
        if ($this->isWritable()) {
            if (null !== $meaning = $message->getMeaning()) {
                $this->setMeaning($meaning);
            }

            if (null !== $desc = $message->getDesc()) {
                $this->setDesc($desc);
            }

            $this->setNew($message->isNew());
            if ($localeString = $message->getLocaleString()) {
                $this->setLocaleString($localeString);
            }
        }
        $this->mergeXliffMeta($message, $oldDesc);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeExisting(Message $message): void
    {
        if ($this->getId() !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->getId(), $message->getId()));
        }

        $oldDesc = $this->getDesc();
        if ($this->isWritable()) {
            if (null !== $meaning = $message->getMeaning()) {
                $this->setMeaning($meaning);
            }

            if (null !== $desc = $message->getDesc()) {
                $this->setDesc($desc);
            }

            $this->setNew($message->isNew());
            if ($localeString = $message->getLocaleString()) {
                $this->setLocaleString($localeString);
            }
        }
        $this->mergeXliffMeta($message, $oldDesc);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeScanned(Message $message): void
    {
        if ($this->getId() !== $message->getId()) {
            throw new RuntimeException(sprintf('You can only merge messages with the same id. Expected id "%s", but got "%s".', $this->getId(), $message->getId()));
        }

        $this->setSources($message->getSources());

        $oldDesc = $this->getDesc();
        if ($this->isWritable()) {
            if (null === $this->getMeaning()) {
                $this->setMeaning($message->getMeaning());
            }

            if (null === $this->getDesc()) {
                $this->setDesc($message->getDesc());
            }

            if (!$this->getLocaleString()) {
                $this->setLocaleString($message->getLocaleString());
            }
        }
        $this->mergeXliffMeta($message, $oldDesc);
    }

    /**
     * Merge XLIFF metadata into this message, if description has changed.
     *
     * @param Message $message The message we are merging with
     * @param string|null $oldDesc The description before merging
     */
    protected function mergeXliffMeta(Message $message, ?string $oldDesc): void
    {
        if ($oldDesc !== $this->getDesc()) {
            if ($message instanceof self) {
                $this->setState($message->getState());
                $this->setApproved($message->isApproved());
                $this->setNotes($message->getNotes());
            } else {
                $this->setApproved(false);
            }
        }
    }
}
