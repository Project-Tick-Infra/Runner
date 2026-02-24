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

use App\Module\Cla\ClaCheckService;
use App\Module\GithubBot\GithubBotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:github:sync-commits',
    description: 'Sync all commits for specific repositories into local database',
)]
class GithubSyncCommitsCommand extends Command
{
    private GithubBotService $botService;
    private ClaCheckService $claService;

    public function __construct(GithubBotService $botService, ClaCheckService $claService)
    {
        parent::__construct();
        $this->botService = $botService;
        $this->claService = $claService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GitHub: Syncing Full Commit History');

        try {
            $tokenCount = $this->botService->getDetectedTokensCount();
            if ($tokenCount > 0) {
                $io->note("Token Rotation Active: $tokenCount backup PAT tokens detected.");
            }

            // Get a rotated client (will be a PAT if available)
            $client = $this->botService->getRotatedClient();
            
            $appDiscoverySucceeded = false;
            try {
                // Try App discovery ONLY if we aren't already using a PAT for discovery
                // but for now, let's try it and catch errors
                $installations = $client->api('apps')->findInstallations();
                foreach ($installations as $installation) {
                    $instId = $installation['id'];
                    $instClient = $this->claService->getGithubClient($instId);
                    $repos = $instClient->api('apps')->listRepositories();
                    $targetRepos = $repos['repositories'] ?? $repos;
                    $this->processRepos($targetRepos, $instId, $io);
                    $appDiscoverySucceeded = true;
                }
            } catch (\Exception $e) {
                // If this is a PAT client, it might not support apps()->findInstallations()
                // OR if the App credentials are broken, this will throw the JWT error
                $io->warning("App-based discovery failed or skipped. Using PAT-based organization discovery.");
            }

            if (!$appDiscoverySucceeded) {
                // Fallback: Use the PAT client to list all repos of the Project-Tick org
                $orgName = 'Project-Tick';
                $io->note("Attempting direct discovery for organization: $orgName");
                
                try {
                    $orgRepos = $client->api('organization')->repositories($orgName);
                    $this->processRepos($orgRepos, null, $io);
                } catch (\Exception $e) {
                    $io->error("Organization discovery also failed: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $io->error('Error during commit sync: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function processRepos(array $repos, ?int $instId, SymfonyStyle $io): void
    {
        foreach ($repos as $repoData) {
            $repoFullName = $repoData['full_name'];
            [$owner, $repo] = explode('/', $repoFullName);
            
            $io->section("Syncing Repo: $repoFullName");
            $count = $this->botService->syncAllCommits($owner, $repo, $instId);
            $io->success("Added $count new commits for $repoFullName");
        }
    }
}
