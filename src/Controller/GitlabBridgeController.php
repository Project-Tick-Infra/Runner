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

use App\Module\GitlabBridge\GitlabBridgeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/gitlab')]
class GitlabBridgeController extends AbstractController
{
    private GitlabBridgeService $bridgeService;
    private \App\Service\ModuleService $modules;

    public function __construct(GitlabBridgeService $bridgeService, \App\Service\ModuleService $modules)
    {
        $this->bridgeService = $bridgeService;
        $this->modules = $modules;
    }

    #[Route('/webhook/bridge', name: 'app_gitlab_to_github_bridge', methods: ['POST'])]
    public function handleBridge(Request $request): Response
    {
        if (!$this->modules->isGitlabBridgeEnabled()) {
            return new Response('GitLab Bridge module is DISABLED', 503);
        }
        file_put_contents('/var/www/projt-website/var/log/gitlab_bridge.log', sprintf("[%s] Bridge Triggered: %s\n", date('Y-m-d H:i:s'), $request->getContent()), FILE_APPEND);
        
        $data = $request->toArray();
        $objectKind = $data['object_kind'] ?? '';

        if ($objectKind !== 'merge_request') {
            return new Response('Ignored: Not a merge request event', 200);
        }

        $action = $data['object_attributes']['action'] ?? '';
        if (!in_array($action, ['open', 'update', 'reopen'])) {
             return new Response('Ignored: MR Action not relevant', 200);
        }

        $sourceBranch = $data['object_attributes']['source_branch'];
        $repoFullName = $data['project']['path_with_namespace'];
        $projectId = $data['project']['id'];
        $commitSha = $data['object_attributes']['last_commit_sha'];

        $success = $this->bridgeService->triggerShadowRunner(
            $projectId,
            $repoFullName,
            $sourceBranch,
            $commitSha
        );

        return $success 
            ? new Response('Bridge Triggered Successfully', 200)
            : new Response('Bridge Failed', 500);
    }
}
