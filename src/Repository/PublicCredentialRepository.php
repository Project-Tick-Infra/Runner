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

namespace App\Repository;

use App\Entity\PublicCredential;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\Bundle\Repository\CanSaveCredentialSource;

/**
 * @extends ServiceEntityRepository<PublicCredential>
 */
class PublicCredentialRepository extends ServiceEntityRepository implements PublicKeyCredentialSourceRepositoryInterface, PublicKeyCredentialUserEntityRepositoryInterface, CanSaveCredentialSource
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicCredential::class);
    }

    // --- PublicKeyCredentialSourceRepositoryInterface Implementation ---

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $data = $this->findOneBy(['publicKeyCredentialId' => base64_encode($publicKeyCredentialId)]);
        if (!$data) return null;

        return $this->createSourceFromEntity($data);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sources = [];
        $entities = $this->findBy(['userHandle' => $publicKeyCredentialUserEntity->getId()]);
        foreach ($entities as $entity) {
            $sources[] = $this->createSourceFromEntity($entity);
        }
        return $sources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $entity = $this->findOneBy(['publicKeyCredentialId' => base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId())]) ?? new PublicCredential();
        
        // Find user by handle
        $user = $this->getEntityManager()->getRepository(User::class)->find($publicKeyCredentialSource->getUserHandle());
        
        $entity->setUser($user);
        $entity->setPublicKeyCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
        $entity->setPublicKey(base64_encode($publicKeyCredentialSource->getCredentialPublicKey()));
        $entity->setSignCount($publicKeyCredentialSource->getCounter());
        $entity->setUserHandle($publicKeyCredentialSource->getUserHandle());
        $entity->setTransports($publicKeyCredentialSource->getTransports());
        $entity->setAttestationType($publicKeyCredentialSource->getAttestationType());
        $entity->setCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
        $entity->setTrustPath($publicKeyCredentialSource->getTrustPath()->jsonSerialize());
        $entity->setAaguid($publicKeyCredentialSource->getAaguid()->toString());

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    // --- PublicKeyCredentialUserEntityRepositoryInterface Implementation ---

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $username]);
        if (!$user) return null;

        return new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getUsername() ?? $user->getEmail(),
            null
        );
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userHandle);
        if (!$user) return null;

        return new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getUsername() ?? $user->getEmail(),
            null
        );
    }

    // --- Helper ---

    private function createSourceFromEntity(PublicCredential $entity): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            base64_decode($entity->getPublicKeyCredentialId()),
            $entity->getAttestationType(),
            $entity->getTransports(),
            $entity->getAttestationType(),
            \Webauthn\TrustPath\EmptyTrustPath::create(),
            Uuid::fromString($entity->getAaguid()),
            base64_decode($entity->getPublicKey()),
            $entity->getUserHandle(),
            $entity->getSignCount()
        );
    }
}
