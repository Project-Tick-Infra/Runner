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

namespace App\Command;

use App\Module\GitlabBridge\GitlabBridgeService;
use App\Service\ModuleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gitlab-observer',
    description: 'Polls GitLab for new Merge Requests and triggers GitHub Shadow Runner if needed.',
)]
class GitlabShadowObserverCommand extends Command
{
    private GitlabBridgeService $bridgeService;
    private ModuleService $modules;

    public function __construct(GitlabBridgeService $bridgeService, ModuleService $modules)
    {
        parent::__construct();
        $this->bridgeService = $bridgeService;
        $this->modules = $modules;
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', null, 'Force trigger all active Merge Requests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $io = new SymfonyStyle($input, $output);
        $io->title('GitLab Shadow Observer' . ($force ? ' [FORCE MODE]' : ''));

        if (!$this->modules->isGitlabBridgeEnabled()) {
            $io->warning('GitLab Bridge module is DISABLED. Skipping observation.');
            return Command::SUCCESS;
        }

        $projects = $this->bridgeService->getGitlabProjects();
        $io->info(sprintf('Checking %d projects...', count($projects)));

        foreach ($projects as $project) {
            $projectId = $project['id'];
            $projectName = $project['path_with_namespace'];

            $mrs = $this->bridgeService->getOpenMergeRequests($projectId);

            if (empty($mrs)) {
                continue;
            }

            foreach ($mrs as $mr) {
                $mrId = $mr['iid'];
                $sha = $mr['sha'];
                $sourceBranch = $mr['source_branch'];

                $io->text("Checking MR !{$mrId} in {$projectName} (SHA: {$sha})");

                // Statüleri kontrol et
                $hasShadowRunner = false;
                if (!$force) {
                    $statuses = $this->bridgeService->getCommitStatuses($projectId, $sha);
                    foreach ($statuses as $status) {
                        $context = $status['context'] ?? $status['name'] ?? '';
                        if (str_starts_with($context, 'Shadow-Runner')) {
                            // Sadece başarılı veya devam edenleri geç, hata almışsa tekrar denebilir
                            if ($status['status'] === 'success' || $status['status'] === 'running' || $status['status'] === 'pending') {
                                $hasShadowRunner = true;
                                break;
                            }
                        }
                    }
                }

                if (!$hasShadowRunner) {
                    $io->warning(($force ? "[FORCE] " : "") . "Shadow Runner not found or forced for MR !{$mrId}. Triggering now...");
                    $githubPath = $this->bridgeService->mapGitlabToGithub($projectName);
                    $io->text("Mapped to GitHub: " . $githubPath);

                    $success = $this->bridgeService->triggerShadowRunner(
                        $projectId,
                        $projectName,
                        $sourceBranch, // Changed from $branch to $sourceBranch to maintain correctness
                        $sha,
                        $mrId
                    );

                    if ($success) {
                        $io->success("Triggered GitHub Actions for {$projectName}");
                    } else {
                        $io->error("Failed to trigger GitHub Actions for {$projectName}");
                    }
                } else {
                    $io->note("Shadow Runner already active or finished for MR !{$mrId}");
                }
            }
        }

        $io->success('Observation cycle completed.');

        return Command::SUCCESS;
    }
}
