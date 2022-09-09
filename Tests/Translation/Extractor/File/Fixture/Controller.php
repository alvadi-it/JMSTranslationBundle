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

namespace JMS\TranslationBundle\Tests\Translation\Extractor\File\Fixture;

use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This is a sample controller class.
 *
 * It is used in unit tests to extract translations, and their respective description,
 * and meaning if specified.
 *
 * @author johannes
 */
class Controller
{
    private TranslatorInterface $translator;
    private Session $session;

    public function __construct(TranslatorInterface $translator, Session $session)
    {
        $this->translator = $translator;
        $this->session    = $session;
    }

    public function indexAction(): void
    {
        $this->session->getFlashBag()('foo', $this->translator->trans(/** @Desc("Foo bar") */ 'text.foo_bar'));
    }

    public function welcomeAction(): void
    {
        $this->session->getFlashBag()(
            'bar',
            /** @Desc("Welcome %name%! Thanks for signing up.") */
            $this->translator->trans('text.sign_up_successful', ['name' => 'Johannes'])
        );
    }

    public function foobarAction(): void
    {
        $this->session->getFlashBag()(
            'archive',
            /** @Desc("Archive Message") @Meaning("The verb (to archive), describes an action") */
            $this->translator->trans('button.archive')
        );
    }

    public function nonExtractableButIgnoredAction(): void
    {
        /** @Ignore */ $this->translator->trans($foo);
        /** Foobar */
        /** @Ignore */ $this->translator->trans('foo', [], $baz);
    }

    public function irrelevantDocComment(): void
    {
        /** @Foo @Bar */ $this->translator->trans('text.irrelevant_doc_comment', [], 'baz');
    }

    public function arrayAccess(): void
    {
        $arr['foo']->trans('text.array_method_call');
    }

    public function assignToVar(): string
    {
        /** @Desc("The var %foo% should be assigned.") */
        return $this->translator->trans('text.var.assign', ['%foo%' => 'fooVar']);
    }
}
