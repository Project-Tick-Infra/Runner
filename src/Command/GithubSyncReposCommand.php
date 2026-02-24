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

use App\Entity\GithubRepository;
use App\Repository\GithubRepositoryRepository;
use App\Service\GithubTokenBalancer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:github:sync-repos',
    description: 'Sync repositories from GitHub App installations to database.',
)]
class GithubSyncReposCommand extends Command
{
    private GithubTokenBalancer $balancer;
    private GithubRepositoryRepository $repoRepo;
    private EntityManagerInterface $em;

    public function __construct(
        GithubTokenBalancer $balancer,
        GithubRepositoryRepository $repoRepo,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->balancer = $balancer;
        $this->repoRepo = $repoRepo;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing GitHub Repositories');

        try {
            $client = $this->balancer->getBotClient();
            $installations = $client->api('apps')->findInstallations();

            foreach ($installations as $installation) {
                $instId = $installation['id'];
                $io->note("Processing installation $instId for " . ($installation['account']['login'] ?? 'unknown'));
                
                $instClient = $this->balancer->getBotClient($instId);
                $repos = $instClient->api('apps')->listRepositories();
                $targetRepos = $repos['repositories'] ?? $repos;

                foreach ($targetRepos as $repoData) {
                    $fullName = $repoData['full_name'];
                    
                    $repo = $this->repoRepo->findOneBy(['fullName' => $fullName]);
                    if (!$repo) {
                        $repo = new GithubRepository();
                        $repo->setFullName($fullName);
                        $io->success("New repository found: $fullName");
                    }
                    
                    $repo->setInstallationId($instId);
                    $this->em->persist($repo);
                }
            }

            $this->em->flush();
            $io->success('Sync completed.');

        } catch (\Exception $e) {
            $io->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
