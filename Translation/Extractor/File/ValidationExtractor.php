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

namespace JMS\TranslationBundle\Translation\Extractor\File;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Twig\Node\Node as TwigNode;

/**
 * Extracts translations validation constraints.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ValidationExtractor implements FileVisitorInterface, NodeVisitor
{
    private MetadataFactoryInterface $metadataFactory;

    /**
     * @var NodeTraverser
     */
    private NodeTraverser $traverser;

    /**
     * @var MessageCatalogue
     */
    private MessageCatalogue $catalogue;

    /**
     * @var string
     */
    private string $namespace = '';

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;

        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    /**
     * @param Node $node
     *
     * @return void
     * @throws \ReflectionException
     */
    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            if (isset($node->name)) {
                $this->namespace = property_exists($node->name, 'parts') ? implode('\\', $node->name->parts) : $node->name->name;
            }

            return;
        }

        if (!$node instanceof Node\Stmt\Class_) {
            return;
        }

        $name = '' === $this->namespace ? (string) $node->name : $this->namespace . '\\' . $node->name;

        if (!class_exists($name)) {
            return;
        }

        $metadata = $this->metadataFactory->getMetadataFor($name);
        if (!$metadata->hasConstraints() && !count($metadata->getConstrainedProperties())) {
            return;
        }

        $this->extractFromConstraints($metadata->constraints);
        foreach ($metadata->members as $members) {
            foreach ($members as $member) {
                $this->extractFromConstraints($member->constraints);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param MessageCatalogue $catalogue
     * @param array $ast
     */
    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast): void
    {
        $this->namespace = '';
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }

    /**
     * @param array $nodes
     *
     * @return void
     */
    public function beforeTraverse(array $nodes): void
    {
    }

    /**
     * @param Node $node
     *
     * @return void
     */
    public function leaveNode(Node $node): void
    {
    }

    /**
     * @param array $nodes
     *
     * @return void
     */
    public function afterTraverse(array $nodes): void
    {
    }

    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue): void
    {
    }

    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, TwigNode $ast): void
    {
    }

    /**
     * @param array $constraints
     *
     * @throws \ReflectionException
     */
    private function extractFromConstraints(array $constraints)
    {
        foreach ($constraints as $constraint) {
            $ref = new \ReflectionClass($constraint);
            $defaultValues = $ref->getDefaultProperties();
            $defaultParameters = null !== $ref->getConstructor() ? $ref->getConstructor()->getParameters() : [];
            $properties = $ref->getProperties();

            foreach ($properties as $property) {
                $propName = $property->getName();

                // If the property ends with 'Message'
                if (strtolower(substr($propName, -1 * strlen('Message'))) === 'message') {
                    // If it is different from the default value
                    if (array_key_exists($propName, $defaultValues) && $defaultValues[$propName] !== $constraint->{$propName}) {
                        $message = new Message($constraint->{$propName}, 'validators');
                        $this->catalogue->add($message);
                    } elseif (method_exists($property, 'isPromoted') && $property->isPromoted()) {
                        foreach ($defaultParameters as $defaultParameter) {
                            if ($defaultParameter->getName() === $propName && $defaultParameter->isDefaultValueAvailable() && $defaultParameter->getDefaultValue() !== $constraint->{$propName}) {
                                $message = new Message($constraint->{$propName}, 'validators');
                                $this->catalogue->add($message);

                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}
