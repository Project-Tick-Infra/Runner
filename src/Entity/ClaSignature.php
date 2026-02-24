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

use App\Repository\ClaSignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClaSignatureRepository::class)]
class ClaSignature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'claSignatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $version = '1.0';

    #[ORM\Column(length: 255)]
    private ?string $githubUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gitlabUsername = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sha = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $signatureImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signerName = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nonce = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $githubNumericId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gitlabNumericId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $emailHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $serverSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $pdfHash = null;

    public function __construct()
    {
        $this->signedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getGithubUsername(): ?string
    {
        return $this->githubUsername;
    }

    public function setGithubUsername(string $githubUsername): static
    {
        $this->githubUsername = $githubUsername;
        return $this;
    }

    public function getGitlabUsername(): ?string
    {
        return $this->gitlabUsername;
    }

    public function setGitlabUsername(?string $gitlabUsername): static
    {
        $this->gitlabUsername = $gitlabUsername;
        return $this;
    }

    public function getSha(): ?string
    {
        return $this->sha;
    }

    public function setSha(?string $sha): static
    {
        $this->sha = $sha;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    public function getSignatureImage(): ?string
    {
        return $this->signatureImage;
    }

    public function setSignatureImage(?string $signatureImage): static
    {
        $this->signatureImage = $signatureImage;
        return $this;
    }

    public function getSignerName(): ?string
    {
        return $this->signerName;
    }

    public function setSignerName(?string $signerName): static
    {
        $this->signerName = $signerName;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(?string $nonce): static
    {
        $this->nonce = $nonce;
        return $this;
    }

    public function getGithubNumericId(): ?string
    {
        return $this->githubNumericId;
    }

    public function setGithubNumericId(?string $githubNumericId): static
    {
        $this->githubNumericId = $githubNumericId;
        return $this;
    }

    public function getGitlabNumericId(): ?string
    {
        return $this->gitlabNumericId;
    }

    public function setGitlabNumericId(?string $gitlabNumericId): static
    {
        $this->gitlabNumericId = $gitlabNumericId;
        return $this;
    }

    public function getEmailHash(): ?string
    {
        return $this->emailHash;
    }

    public function setEmailHash(?string $emailHash): static
    {
        $this->emailHash = $emailHash;
        return $this;
    }

    public function getServerSignature(): ?string
    {
        return $this->serverSignature;
    }

    public function setServerSignature(?string $serverSignature): static
    {
        $this->serverSignature = $serverSignature;
        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;
        return $this;
    }

    public function getPdfHash(): ?string
    {
        return $this->pdfHash;
    }

    public function setPdfHash(?string $pdfHash): static
    {
        $this->pdfHash = $pdfHash;
        return $this;
    }
}
