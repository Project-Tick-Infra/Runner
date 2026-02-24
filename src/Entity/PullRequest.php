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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PullRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $repository;

    #[ORM\Column]
    private int $number;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 100)]
    private string $author;

    #[ORM\Column(length: 20)]
    private string $state;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $labels = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $htmlUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $commits = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diff = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLabels(): array
    {
        return $this->labels ?? [];
    }

    public function setLabels(?array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): self
    {
        $this->htmlUrl = $htmlUrl;
        return $this;
    }

    public function getCommits(): array
    {
        return $this->commits ?? [];
    }

    public function setCommits(?array $commits): self
    {
        $this->commits = $commits;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getDiff(): ?string
    {
        return $this->diff;
    }

    public function setDiff(?string $diff): self
    {
        $this->diff = $diff;
        return $this;
    }
}
