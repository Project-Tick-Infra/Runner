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
use App\Service\ModuleService;
use Github\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:github:bot-scan',
    description: 'Scan all open pull requests and apply bot logic (labels, DCO, CI summary, etc.)',
)]
class GithubBotScanCommand extends Command
{
    private GithubBotService $botService;
    private ClaCheckService $claService;
    private ModuleService $modules;

    public function __construct(GithubBotService $botService, ClaCheckService $claService, ModuleService $modules)
    {
        parent::__construct();
        $this->botService = $botService;
        $this->claService = $claService;
        $this->modules = $modules;
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit the number of PRs to process', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int)$input->getOption('limit');

        $io->title('GitHub Bot: Scanning Open Pull Requests');

        if (!$this->modules->isGithubBotEnabled()) {
            $io->warning('GitHub Bot module is DISABLED. Skipping scan.');
            return Command::SUCCESS;
        }

        try {
            // installations discovery REQUIRES App identity (JWT)
            $client = $this->claService->getGithubClient();
            $installations = $client->api('apps')->findInstallations();

            if (empty($installations)) {
                $io->warning('No installations found for the GitHub App.');
                return Command::SUCCESS;
            }

            foreach ($installations as $installation) {
                $instId = $installation['id'];
                
                // For listing repositories and PRs, we can use the PAT pool (ScannerClient)
                // if the repos are accessible to those tokens. 
                // Alternatively, we use the Installation token but it's cached now.
                $instClient = $this->claService->getScannerClient();
                
                // If the PAT doesn't have access to list App repositories, 
                // we might need to use the instClient with installationId.
                // But let's try using the PAT pool for the bulk work.
                
                // If searching by installation is needed, we must use getGithubClient($instId)
                // which is now CACHED for 55 mins. This is safe!
                $instClient = $this->claService->getGithubClient($instId);
                
                $repos = $instClient->api('apps')->listRepositories();
                $targetRepos = $repos['repositories'] ?? $repos;
                
                foreach ($targetRepos as $repoData) {
                    $repoName = $repoData['full_name'];
                    [$owner, $repo] = explode('/', $repoName);
                    
                    $io->section("Processing Repository: $repoName");
                    
                    // Sync Open PRs (and process bot logic)
                    $openPrs = $instClient->api('pull_request')->all($owner, $repo, ['state' => 'open']);
                    $count = 0;
                    foreach ($openPrs as $pr) {
                        if ($count >= $limit) break;
                        $io->text("-> Open PR #{$pr['number']}: {$pr['title']}");
                        $this->botService->handlePullRequest($owner, $repo, $pr['number'], $instId);
                        $this->botService->syncPullRequestToDb($pr, $repoName, $instId);
                        $count++;
                    }

                    // Sync Closed PRs (sync only)
                    $closedPrs = $instClient->api('pull_request')->all($owner, $repo, ['state' => 'closed', 'per_page' => 20]);
                    foreach ($closedPrs as $pr) {
                        $io->text("-> Closed PR #{$pr['number']} (Sync Only)");
                        $this->botService->syncPullRequestToDb($pr, $repoName, $instId);
                    }
                    
                    $io->success("Synced items in $repoName");
                }
            }
        } catch (\Exception $e) {
            $io->error('Error during scan: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
