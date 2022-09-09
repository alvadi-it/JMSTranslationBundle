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

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Annotation\Meaning;
use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Psr\Log\LoggerInterface;
use Twig\Node\Node as TwigNode;

/**
 * This parser can extract translation information from PHP files.
 *
 * It parses all calls that are made to a method named "trans".
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DefaultPhpFileExtractor implements LoggerAwareInterface, FileVisitorInterface, NodeVisitor
{
    /**
     * @var FileSourceFactory
     */
    private FileSourceFactory $fileSourceFactory;

    /**
     * @var NodeTraverser
     */
    private NodeTraverser $traverser;

    /**
     * @var MessageCatalogue
     */
    private MessageCatalogue $catalogue;

    /**
     * @var \SplFileInfo
     */
    private \SplFileInfo $file;

    /**
     * @var DocParser
     */
    private DocParser $docParser;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Node
     */
    private Node $previousNode;

    /**
     * Methods and "domain" parameter offset to extract from PHP code
     *
     * @var array method => position of the "domain" parameter
     */
    protected array $methodsToExtractFrom = [
        'trans' => 2,
        'transchoice' => 3,
    ];

    public function __construct(DocParser $docParser, FileSourceFactory $fileSourceFactory)
    {
        $this->docParser = $docParser;
        $this->fileSourceFactory = $fileSourceFactory;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Node $node
     *
     * @return void
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function enterNode(Node $node): void
    {
        $methodCallNodeName = null;
        if ($node instanceof Node\Expr\MethodCall) {
            $methodCallNodeName = $node->name instanceof Node\Identifier ? $node->name->name : $node->name;
        }
        if (
            !is_string($methodCallNodeName)
            || !in_array(strtolower($methodCallNodeName), array_map('strtolower', array_keys($this->methodsToExtractFrom)))
        ) {
            $this->previousNode = $node;

            return;
        }

        $ignore = false;
        $desc = $meaning = null;
        if (null !== $docComment = $this->getDocCommentForNode($node)) {
            foreach ($this->docParser->parse($docComment, 'file ' . $this->file . ' near line ' . $node->getLine()) as $annot) {
                if ($annot instanceof Ignore) {
                    $ignore = true;
                } elseif ($annot instanceof Desc) {
                    $desc = $annot->text;
                } elseif ($annot instanceof Meaning) {
                    $meaning = $annot->text;
                }
            }
        }

        if (!$node->args[0]->value instanceof String_) {
            if ($ignore) {
                return;
            }

            $message = sprintf('Can only extract the translation id from a scalar string, but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[0]->value), $this->file, $node->args[0]->value->getLine());

            $this->logger->error($message);

            return;
        }

        $id = $node->args[0]->value->value;

        $index = $this->methodsToExtractFrom[strtolower($methodCallNodeName)];
        if (isset($node->args[$index])) {
            if ($node->args[$index]->value instanceof Node\Expr\ConstFetch && 'null' === (string) $node->args[$index]->value->name) {
                $domain = 'messages';
            } elseif ($node->args[$index]->value instanceof String_) {
                $domain = $node->args[$index]->value->value;
            } else {
                if ($ignore) {
                    return;
                }

                $message = sprintf('Can only extract the translation domain from a scalar string, but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[$index]->value), $this->file, $node->args[$index]->value->getLine());

                $this->logger->error($message);

                return;
            }
        } else {
            $domain = 'messages';
        }

        $message = new Message($id, $domain);
        $message->setDesc($desc);
        $message->setMeaning($meaning);
        $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
        $this->catalogue->add($message);
    }

    /**
     * @param \SplFileInfo $file
     * @param MessageCatalogue $catalogue
     * @param array $ast
     */
    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast): void
    {
        $this->file = $file;
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
     * @param Node $node
     *
     * @return string|null
     */
    private function getDocCommentForNode(Node $node): ?string
    {
        // check if there is a doc comment for the ID argument
        // ->trans(/** @Desc("FOO") */ 'my.id')
        if (null !== $comment = $node->args[0]->getDocComment()) {
            return $comment->getText();
        }

        // this may be placed somewhere up in the hierarchy,
        // -> /** @Desc("FOO") */ trans('my.id')
        // /** @Desc("FOO") */ ->trans('my.id')
        // /** @Desc("FOO") */ $translator->trans('my.id')
        if (null !== $comment = $node->getDocComment()) {
            return $comment->getText();
        }

        if (null !== $this->previousNode && $this->previousNode->getDocComment() !== null) {
            $comment = $this->previousNode->getDocComment();

            return is_object($comment) ? $comment?->getText() : $comment;
        }

        return null;
    }
}
