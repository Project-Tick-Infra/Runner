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

namespace App\Controller;

use App\Entity\ClaSignature;
use App\Entity\License;
use App\Entity\User;
use App\Module\Cla\ClaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\SiteSettingRepository;
use Symfony\Component\Uid\Uuid;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/dashboard', name: 'app_user_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $gravatarHash = md5(strtolower(trim($user->getEmail())));

        $activities = [];

        // 1. Real License Signatures
        foreach ($user->getUserLicenses() as $lic) {
            $activities[] = [
                'title' => 'Signed License: ' . $lic->getLicense()->getName(),
                'date' => $lic->getSignedAt(),
                'type' => 'license'
            ];
        }

        // 2. Real CLA Signatures
        foreach ($user->getClaSignatures() as $cla) {
            $activities[] = [
                'title' => 'Accepted CLA Version ' . $cla->getVersion(),
                'date' => $cla->getSignedAt(),
                'type' => 'license'
            ];
        }

        // 3. Real Roadmap Interest
        $roadmapItems = $em->getRepository(\App\Entity\RoadmapItem::class)->findAll();
        foreach ($roadmapItems as $item) {
            if ($item->getVotes()->contains($user)) {
                $activities[] = [
                    'title' => 'Upvoted Roadmap: ' . $item->getTitle(),
                    'date' => $item->getCreatedAt(), 
                    'type' => 'roadmap'
                ];
            }
        }

        // Sort by date DESC
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Limit to top 5
        $activities = array_slice($activities, 0, 5);

        // CLA Status check
        $latestCla = 'PT-CLA-2.0';
        $isClaSigned = false;
        $isClaVerified = false;
        foreach ($user->getClaSignatures() as $sig) {
            if ($sig->getVersion() === $latestCla) {
                $isClaSigned = true;
                $isClaVerified = $sig->isVerified();
                break;
            }
        }

        return $this->render('user/dashboard.html.twig', [
            'user' => $user,
            'gravatarHash' => $gravatarHash,
            'activities' => $activities,
            'tick_token' => $user->getTickApiToken(),
            'is_cla_signed' => $isClaSigned,
            'is_cla_verified' => $isClaVerified,
            'cla_slug' => $latestCla
        ]);
    }

    #[Route('/licenses', name: 'app_user_licenses', methods: ['GET'])]
    public function myLicenses(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $licenses = $user->getUserLicenses();
        
        // Check for specific CLA versions if needed
        // Dynamically find the latest CLA (Order by slug DESC, e.g., PT-CLA-3.0 > PT-CLA-2.0)
        $latestCla = $em->getRepository(License::class)->createQueryBuilder('l')
            ->where('l.slug LIKE :cla')
            ->setParameter('cla', 'PT-CLA-%')
            ->orderBy('l.slug', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $isClaSigned = false;
        $isClaVerified = false;
        $claSlug = $latestCla ? $latestCla->getSlug() : null;

        if ($latestCla) {
            foreach ($user->getClaSignatures() as $sig) {
                if ($sig->getVersion() === $claSlug) {
                    $isClaSigned = true;
                    $isClaVerified = $sig->isVerified();
                    break;
                }
            }
        }

        return $this->render('user/licenses.html.twig', [
            'user_licenses' => $licenses,
            'isClaSigned' => $isClaSigned,
            'isClaVerified' => $isClaVerified,
            'claSlug' => $claSlug
        ]);
    }

    #[Route('/cla/view/{slug}', name: 'app_user_cla_view', methods: ['GET'])]
    public function viewCla(string $slug, EntityManagerInterface $em, SiteSettingRepository $settings): Response
    {
        $user = $this->getUser();
        $license = $em->getRepository(License::class)->findOneBy(['slug' => $slug]);
        if (!$license) {
            throw $this->createNotFoundException('CLA not found');
        }

        // Generate SHA-256 for display (Ritual Hash)
        // Normalize: Email is always lowercase in our system, but let's be safe.
        $contentToHash = $license->getContent() . 
                         strtolower(trim($user->getEmail())) . 
                         $user->getGithubUsername() . 
                         ($user->getGitlabUsername() ?: '');
        $ritualSha = hash('sha256', $contentToHash);
        
        // Raw Document Hash
        $docSha = hash('sha256', $license->getContent());

        // Get Agreement Template from Settings
        $agreementTemplate = $settings->getValue('cla_agreement_template', 'I, [NAME], HEREBY ASSIGN AND TRANSFER ALL RIGHT, TITLE, AND INTEREST IN AND TO MY CONTRIBUTIONS UNDER PROJECT TICK CLA [SLUG]');
        
        $isSigned = false;
        foreach ($user->getClaSignatures() as $sig) {
            if ($sig->getVersion() === $slug && $sig->isVerified()) {
                $isSigned = true;
                break;
            }
        }

        return $this->render('user/cla_sign.html.twig', [
            'license' => $license,
            'sha' => $ritualSha,
            'doc_sha' => $docSha,
            'already_signed' => $isSigned,
            'github_id' => $user->getGithubId(),
            'gitlab_id' => $user->getGitlabId(),
            'email_hash' => hash('sha256', strtolower(trim($user->getEmail()))),
            'agreement_template' => $agreementTemplate
        ]);
    }

    #[Route('/cla/sign/{slug}', name: 'app_user_cla_sign', methods: ['POST'])]
    public function signCla(string $slug, EntityManagerInterface $em, Request $request, ClaService $claService, SiteSettingRepository $settings): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // CSRF Check
        if (!$this->isCsrfTokenValid('sign_cla', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_user_cla_view', ['slug' => $slug]);
        }
        
        $license = $em->getRepository(License::class)->findOneBy(['slug' => $slug]);
        if (!$license) {
            throw $this->createNotFoundException('CLA not found');
        }

        // 1. Digital Ritual Verification
        $shaInput = $request->request->get('sha_verify');
        $agreementText = $request->request->get('agreement_text');
        $signatureImage = $request->request->get('signature_image');
        $signerName = $request->request->get('signer_name');
        // Check GitLab account
        if (!$user->getGitlabUsername() || !$user->getGitlabId()) {
            $this->addFlash('error', 'You must link your GitLab account before signing the CLA.');
            return $this->redirectToRoute('app_user_settings');
        }

        // Check SHA (Last 6 chars)
        $expectedSha = hash('sha256', 
            $license->getContent() . 
            strtolower(trim($user->getEmail())) . 
            $user->getGithubUsername() . 
            $user->getGitlabUsername()
        );
        $expectedLast6 = substr($expectedSha, -6);

        if (empty($shaInput) || strtolower(trim($shaInput)) !== strtolower($expectedLast6)) {
            $this->addFlash('error', 'SHA Verification failed. Please ensure you entered the last 6 characters correctly.');
            return $this->redirectToRoute('app_user_cla_view', ['slug' => $slug]);
        }

        // Check Agreement Text - Normalize for Comparison
        $signerNameNormalized = mb_strtoupper(trim($signerName ?: $user->getGithubUsername()));
        
        $agreementTemplate = $settings->getValue('cla_agreement_template', 'I, [NAME], HEREBY ASSIGN AND TRANSFER ALL RIGHT, TITLE, AND INTEREST IN AND TO MY CONTRIBUTIONS UNDER PROJECT TICK CLA [SLUG]');
        $expectedText = str_replace(['[NAME]', '[SLUG]'], [$signerNameNormalized, strtoupper($slug)], $agreementTemplate);
        
        // Final normalization of input: remove extra spaces, trim, uppercase
        $cleanedAgreementText = preg_replace('/\s+/', ' ', mb_strtoupper(trim($agreementText)));
        $cleanedExpectedText = preg_replace('/\s+/', ' ', mb_strtoupper(trim($expectedText)));

        if (empty($agreementText) || $cleanedAgreementText !== $cleanedExpectedText) {
            $this->addFlash('error', 'Agreement ritual phrase is incorrect. You must type exactly what is shown in the preview (spacing and commas matter).');
            return $this->redirectToRoute('app_user_cla_view', ['slug' => $slug]);
        }

        if (empty($signatureImage) || strlen($signatureImage) < 100) {
            $this->addFlash('error', 'Your digital signature is required.');
            return $this->redirectToRoute('app_user_cla_view', ['slug' => $slug]);
        }

        if (empty($signerName)) {
            $this->addFlash('error', 'Your full legal name is required.');
            return $this->redirectToRoute('app_user_cla_view', ['slug' => $slug]);
        }

        // Advanced Hashing Logic (Canonical Form)
        $timestamp = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $ip = $request->getClientIp();
        $ua = $request->headers->get('User-Agent');
        $nonce = bin2hex(random_bytes(32)); // 64 chars
        
        $docSha = hash('sha256', $license->getContent());
        $emailHash = hash('sha256', strtolower(trim($user->getEmail())));
        $sigImageHash = hash('sha256', $signatureImage);
        $githubId = (string) $user->getGithubId();
        $gitlabId = (string) $user->getGitlabId();
        
        // CANONICAL PAYLOAD DEFINITION (Strict Order)
        // Order: DocSha|SignerName|GitHubId|GitLabId|EmailHash|SigImageHash|Timestamp|IP|UA|Nonce
        $canonicalPayload = implode('|', [
            $docSha,
            strtoupper(trim($signerName)),
            $githubId,
            $gitlabId,
            $emailHash,
            $sigImageHash,
            $timestamp->format('Y-m-d\TH:i:s\Z'),
            $ip,
            trim($ua),
            $nonce
        ]);

        $eventSha = hash('sha256', $canonicalPayload);

        // Server-Side Digital Seal (Ed25519)
        // Using APP_SECRET as a seed to derive a stable server key if no dedicated key is found
        $appSecret = $this->getParameter('kernel.secret');
        $seed = hash('sha256', $appSecret, true);
        $keyPair = sodium_crypto_sign_seed_keypair($seed);
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        
        $serverSignature = bin2hex(sodium_crypto_sign_detached($eventSha, $secretKey));

        // Check if existing but unverified signature exists
        $signature = null;
        foreach ($user->getClaSignatures() as $sig) {
            if ($sig->getVersion() === $slug) {
                $signature = $sig;
                break;
            }
        }

        if (!$signature) {
            $signature = new ClaSignature();
            $signature->setUser($user);
            $signature->setGithubUsername($user->getGithubUsername());
            $signature->setGitlabUsername($user->getGitlabUsername());
            $signature->setVersion($slug);
        }

        $signature->setSha($eventSha);
        $signature->setSignatureImage($signatureImage);
        $signature->setSignerName($signerName);
        $signature->setIpAddress($ip);
        $signature->setUserAgent($ua);
        $signature->setNonce($nonce);
        $signature->setGithubNumericId($githubId);
        $signature->setGitlabNumericId($gitlabId);
        $signature->setEmailHash($emailHash);
        $signature->setServerSignature($serverSignature);
        $signature->setIsVerified(true);
        $signature->setVerifiedAt($timestamp);
        $signature->setSignedAt($timestamp);
        $signature->setVerificationToken(null);
        
        $em->persist($signature);
        $em->flush();
        
        // --- IMMUTABLE PDF GENERATION VIA SERVICE ---
        try {
            $claService->generatePdfRecord($signature);
        } catch (\Exception $e) {
            // Log error in a real production app
        }

        $this->addFlash('success', 'The Digital Ritual is complete. Your CLA signature is now verified and active.');
        
        return $this->redirectToRoute('app_user_licenses');
    }

    #[Route('/cla/print/{id}', name: 'app_user_cla_print', methods: ['GET'])]
    public function printCla(int $id, EntityManagerInterface $em, ClaService $claService): Response
    {
        $signature = $em->getRepository(ClaSignature::class)->find($id);
        
        if (!$signature || $signature->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this signature.');
        }

        $pdfPath = $claService->getOfficialPdfPath($signature);

        return new Response(file_get_contents($pdfPath), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="ProjectTick_CLA_%s.pdf"', $signature->getVersion()),
        ]);
    }

    #[Route('/settings', name: 'app_user_settings')]
    public function settings(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        
        // Profile Form
        $formProfile = $this->createForm(\App\Form\UserProfileType::class, $user);
        $formProfile->handleRequest($request);

        if ($formProfile->isSubmitted() && $formProfile->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $avatarFile */
            $avatarFile = $formProfile->get('avatar')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

                try {
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/avatars',
                        $newFilename
                    );
                    $user->setAvatar($newFilename);
                } catch (FileException $e) {
                     $this->addFlash('error', 'Error uploading avatar: '.$e->getMessage());
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('app_user_settings');
        }

        // Change Password Form
        $formPassword = $this->createForm(\App\Form\ChangePasswordType::class);
        $formPassword->handleRequest($request);

        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $newPassword = $formPassword->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();
            $this->addFlash('success', 'Password changed successfully.');
            return $this->redirectToRoute('app_user_settings');
        }
        
        return $this->render('user/settings.html.twig', [
            'formProfile' => $formProfile,
            'formPassword' => $formPassword,
            'user' => $user
        ]);
    }

    #[Route('/settings/email-change', name: 'app_user_email_change_request', methods: ['POST'])]
    public function requestEmailChange(Request $request, EntityManagerInterface $em, MailerInterface $mailer, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $newEmail = $request->request->get('email');
        $password = $request->request->get('current_password');

        if (!$this->isCsrfTokenValid('request_email_change', $request->request->get('_token'))) {
             $this->addFlash('error', 'Invalid security token.');
             return $this->redirectToRoute('app_user_settings');
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Invalid password.');
            return $this->redirectToRoute('app_user_settings');
        }

        if ($newEmail === $user->getEmail()) {
            $this->addFlash('error', 'This is already your email address.');
            return $this->redirectToRoute('app_user_settings');
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $newEmail]);
        if ($existingUser) {
            $this->addFlash('error', 'This email address is already in use.');
            return $this->redirectToRoute('app_user_settings');
        }

        $token = Uuid::v4()->toBase32();
        $user->setEmailChangeToken($token);
        $user->setNewEmailPending($newEmail);
        $em->flush();

        $confirmUrl = $this->generateUrl('app_user_email_change_confirm', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $emailMessage = (new Email())
            ->from('noreply@projecttick.org')
            ->to($newEmail)
            ->subject('Confirm your email change')
            ->html($this->renderView('emails/email_change_confirm.html.twig', [
                'confirmUrl' => $confirmUrl,
                'user' => $user
            ]));

        $mailer->send($emailMessage);

        $this->addFlash('success', 'A confirmation email has been sent to ' . $newEmail . '. Please click the link in that email to complete the change.');
        return $this->redirectToRoute('app_user_settings');
    }

    #[Route('/settings/email-confirm/{token}', name: 'app_user_email_change_confirm', methods: ['GET'])]
    public function confirmEmailChange(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['emailChangeToken' => $token]);

        if (!$user || !$user->getNewEmailPending()) {
            $this->addFlash('error', 'Invalid or expired confirmation token.');
            return $this->redirectToRoute('app_user_settings');
        }

        $user->setEmail($user->getNewEmailPending());
        $user->setEmailChangeToken(null);
        $user->setNewEmailPending(null);
        $em->flush();

        $this->addFlash('success', 'Your email address has been updated successfully.');
        return $this->redirectToRoute('app_user_settings');
    }


    #[Route('/delete', name: 'app_user_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
    {
        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
             $this->addFlash('error', 'Invalid token.');
             return $this->redirectToRoute('app_user_settings');
        }
        
        $user = $this->getUser();
        $em->remove($user);
        $em->flush();
        
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        
        $this->addFlash('info', 'Your account has been deleted.');
        return $this->redirectToRoute('app_home');
    }
}
