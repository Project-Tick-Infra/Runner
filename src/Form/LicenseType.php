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

use App\Entity\License;
use App\Entity\LicenseCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('slug', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('licenseCategory', EntityType::class, [
                'class' => LicenseCategory::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('spdxIdentifier', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. GPL-2.0-only'],
            ])
            ->add('usageDocumentation', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Enter suggested usage guidelines here...'],
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 20],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => License::class,
        ]);
    }
}
