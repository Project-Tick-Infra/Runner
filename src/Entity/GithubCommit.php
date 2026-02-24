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
#[ORM\Table(name: 'github_commit')]
#[ORM\UniqueConstraint(name: 'repo_sha_idx', columns: ['repository', 'sha'])]
class GithubCommit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $repository;

    #[ORM\Column(length: 100)]
    private string $sha;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 100)]
    private string $author;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $htmlUrl = null;

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

    public function getSha(): string
    {
        return $this->sha;
    }

    public function setSha(string $sha): self
    {
        $this->sha = $sha;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diff = null;

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
