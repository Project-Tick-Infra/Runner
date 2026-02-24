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

use App\Repository\ClaSignatureRepository;
use Github\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/github')]
class GithubWebhookController extends AbstractController
{
    private string $webhookSecret;
    private \App\Module\GithubBot\GithubBotService $botService;
    private \App\Service\ModuleService $modules;

    public function __construct(string $webhookSecret, \App\Module\GithubBot\GithubBotService $botService, \App\Service\ModuleService $modules)
    {
        $this->webhookSecret = $webhookSecret;
        $this->botService = $botService;
        $this->modules = $modules;
    }

    #[Route('/webhook', name: 'app_github_webhook', methods: ['POST'])]
    public function handleWebhook(
        Request $request, 
        \App\Module\Cla\ClaCheckService $claService
    ): Response {
        if (!$this->modules->isGithubBotEnabled()) {
            return new Response('GitHub Bot module is DISABLED', 503);
        }

        $payloadContent = $request->getContent();
        
        $payload = json_decode($payloadContent, true);
        $event = $request->headers->get('X-GitHub-Event');
        file_put_contents('/var/www/projt-website/var/log/bot_debug.log', sprintf("[%s] Incoming Webhook: %s\n", date('Y-m-d H:i:s'), $event), FILE_APPEND);
        
        // Security Check: Verify Webhook Signature
        if (!empty($this->webhookSecret)) {
            $signature = $request->headers->get('X-Hub-Signature-256');
            if (!$signature) {
                file_put_contents('/var/www/projt-website/var/log/bot_debug.log', "[WARNING] Signature missing\n", FILE_APPEND);
                return new Response('Signature missing', 403);
            }

            $computedSignature = 'sha256=' . hash_hmac('sha256', $payloadContent, $this->webhookSecret);
            
            if (!hash_equals($computedSignature, $signature)) {
                file_put_contents('/var/www/projt-website/var/log/bot_debug.log', "[ERROR] Signature mismatch\n", FILE_APPEND);
                return new Response('Signature mismatch', 403);
            }
        }
        if (!$payload) {
            return new Response('Invalid payload', 400);
        }

        $event = $request->headers->get('X-GitHub-Event');
        $installationId = $payload['installation']['id'] ?? null;

        if ($event === 'pull_request') {
            $action = $payload['action'] ?? '';
            if (!in_array($action, ['opened', 'synchronize', 'reopened', 'labeled', 'unlabeled'])) {
                return new Response('Action ignored', 200);
            }

            $repoName = $payload['repository']['full_name'];
            $prNumber = $payload['number'];
            [$owner, $repo] = explode('/', $repoName);

            // Run CLA Check
            $claService->checkPullRequest($owner, $repo, $prNumber, $installationId);
            
            // Run Bot Logic
            $this->botService->handlePullRequest($owner, $repo, $prNumber, $installationId);
            
            return new Response('Webhook processed', 200);
        }

        if ($event === 'workflow_run') {
            $this->botService->handleWorkflowRun($payload, $installationId);
            return new Response('Workflow Run processed', 200);
        }

        if ($event === 'issue_comment') {
            $this->botService->handleIssueComment($payload, $installationId);
            return new Response('Issue Comment processed', 200);
        }

        return new Response('Event ignored', 200);
    }
}
