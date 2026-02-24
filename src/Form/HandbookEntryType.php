<?php

/*

SPDX-License-Identifier: MIT
SPDX-FileCopyrightText: 2026 Project Tick
SPDX-FileContributor: Project Tick

Copyright (c) 2026 Project Tick

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace App\Form;

use App\Entity\HandbookEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HandbookEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('slug', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('content', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'attr' => ['rows' => 20, 'class' => 'form-control']
            ])
            ->add('category', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Documentation' => 'DOCUMENTATION',
                    'Developer' => 'Developer',
                    'Wiki' => 'WIKI',
                    'Other' => 'OTHER'
                ],
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HandbookEntry::class,
        ]);
    }
}
