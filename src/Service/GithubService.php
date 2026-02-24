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

namespace App\Service;

use Github\Client;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GithubService
{
    private Client $client;
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->client = new Client();
        
        $token = $_ENV['GITHUB_TOKEN'] ?? null;
        if ($token) {
            $this->client->authenticate($token, null, \Github\AuthMethod::ACCESS_TOKEN);
        }

        $this->cache = $cache;
    }

    public function getContributors(string $repoUrl): array
    {
        // Parse owner and repo from URL (e.g. https://github.com/Project-Tick/ProjT-Launcher)
        $path = parse_url($repoUrl, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));

        if (count($parts) < 2) {
            return [];
        }

        $owner = $parts[0];
        $repo = $parts[1];
        $cacheKey = sprintf('github_contributors_%s_%s_v2', $owner, $repo);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($owner, $repo) {
            $item->expiresAfter(86400); // Cache for 24 hours

            try {
                // Get contributors
                // We request up to 500. The API might paginate.
                // The library's ResultPager is best for fetching all/many.
                
                $paginator = new \Github\ResultPager($this->client);
                $parameters = [$owner, $repo];
                
                // Fetch all contributors (automatically paginates)
                $contributors = $paginator->fetchAll($this->client->api('repo'), 'contributors', $parameters);
                
                // Sort by contributions desc (API usually does this but to be safe)
                // Limit to 500
                return array_slice($contributors, 0, 500); 
            } catch (\Exception $e) {
                // In case of error (rate limit, etc.), return empty array
                // Log error if logger was available
                return [];
            }
        });
    }
}
