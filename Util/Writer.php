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

namespace JMS\TranslationBundle\Util;

use JMS\TranslationBundle\Exception\RuntimeException;

/**
 * A writer implementation.
 *
 * This may be used to simplify writing well-formatted code.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Writer
{
    /**
     * @var int
     */
    public int $indentationSpaces = 4;

    /**
     * @var int
     */
    public int $indentationLevel = 0;

    /**
     * @var string
     */
    public string $content = '';

    /**
     * @var int
     */
    public int $changeCount = 0;

    /**
     * @var array
     */
    private array $changes = [];

    /**
     * @return $this
     */
    public function indent(): static
    {
        ++$this->indentationLevel;

        return $this;
    }

    /**
     * @return $this
     */
    public function outdent(): static
    {
        --$this->indentationLevel;

        if ($this->indentationLevel < 0) {
            throw new RuntimeException('The identation level cannot be less than zero.');
        }

        return $this;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function writeln(string $content): static
    {
        $this->write($content . "\n");

        return $this;
    }

    public function revert(): void
    {
        $change = array_pop($this->changes);
        --$this->changeCount;
        $this->content = substr($this->content, 0, -1 * strlen($change));
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function write(string $content): static
    {
        $contentEndsWithNewLine = "\n" === substr($this->content, -1);
        $addition = '';

        $lines = explode("\n", $content);
        for ($i = 0, $c = count($lines); $i < $c; $i++) {
            if (
                $this->indentationLevel > 0
                && !empty($lines[$i])
                && ((empty($addition) && "\n" === substr($this->content, -1)) || "\n" === substr($addition, -1))
            ) {
                $addition .= str_repeat(' ', $this->indentationLevel * $this->indentationSpaces);
            }

            $addition .= $lines[$i];

            if ($i + 1 < $c) {
                $addition .= "\n";
            }
        }

        $this->content .= $addition;
        $this->changes[] = $addition;
        ++$this->changeCount;

        return $this;
    }

    /**
     * @param bool $preserveNewLines
     *
     * @return $this
     */
    public function rtrim($preserveNewLines = true): static
    {
        if (!$preserveNewLines) {
            $this->content = rtrim($this->content);

            return $this;
        }

        $addNl = str_ends_with($this->content, "\n");
        $this->content = rtrim($this->content);

        if ($addNl) {
            $this->content .= "\n";
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->content = '';
        $this->indentationLevel = 0;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
