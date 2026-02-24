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

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\HttpFoundation\RequestStack;

class AbsoluteUrlExtension extends AbstractExtension
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('html_to_absolute_urls', [$this, 'convertToAbsoluteUrls'], ['is_safe' => ['html']]),
        ];
    }

    public function convertToAbsoluteUrls(string $content): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $content;
        }

        $scheme = $request->getScheme();
        $httpHost = $request->getHttpHost();
        $baseUrl = $scheme . '://' . $httpHost;

        // Replace href="/..." with href="baseUrl/..."
        $content = preg_replace_callback('/(href|src)=["\']\/([^"\']*)["\']/', function ($matches) use ($baseUrl) {
            $attribute = $matches[1];
            $path = $matches[2];
            return sprintf('%s="%s/%s"', $attribute, $baseUrl, $path);
        }, $content);

        return $content;
    }
}
