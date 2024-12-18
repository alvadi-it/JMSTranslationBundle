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

class FileSource implements SourceInterface
{
    /**
     * @var string
     */
    private string $path;

    private ?int $line;

    private ?int $column;

    /**
     * @param string $path
     * @param int|null $line
     * @param int|null $column
     */
    public function __construct(string $path, ?int $line = null, ?int $column = null)
    {
        $this->path = $path;
        $this->line = $line;
        $this->column = $column;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getColumn(): ?int
    {
        return $this->column;
    }

    /**
     * @param SourceInterface $source
     *
     * @return bool
     */
    public function equals(SourceInterface $source): bool
    {
        if ($this->path !== $source->getPath()) {
            return false;
        }

        if ($this->line !== $source->getLine()) {
            return false;
        }

        return $this->column === $source->getColumn();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = $this->path;

        if (null !== $this->line) {
            $str .= ' on line ' . $this->line;

            if (null !== $this->column) {
                $str .= ' at column ' . $this->column;
            }
        }

        return $str;
    }
}
