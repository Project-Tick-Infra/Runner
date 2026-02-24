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

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MinecraftAuthService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Complete the Minecraft authentication flow using a Microsoft Access Token.
     * 
     * @param string $msAccessToken The access token from Microsoft OAuth
     * @return array The final Minecraft authentication data
     */
    public function authenticateWithMicrosoft(string $msAccessToken): array
    {
        // 1. Authenticate with Xbox Live
        $xboxTokenData = $this->authenticateXboxLive($msAccessToken);
        $xboxToken = $xboxTokenData['Token'];
        $userHash = $xboxTokenData['DisplayClaims']['xui'][0]['uhs'];

        // 2. Obtain XSTS Token for Minecraft
        $xstsToken = $this->obtainXstsToken($xboxToken);

        // 3. Login with Xbox to Minecraft Services
        $minecraftAuth = $this->loginWithXbox($userHash, $xstsToken);

        // 4. Check Entitlements (Optional but useful)
        $entitlements = $this->checkEntitlements($minecraftAuth['access_token']);

        // 5. Get Profile
        $profile = $this->getProfile($minecraftAuth['access_token']);

        return [
            'minecraft_token' => $minecraftAuth['access_token'],
            'username' => $profile['name'] ?? 'Unknown',
            'uuid' => $profile['id'] ?? null,
            'skins' => $profile['skins'] ?? [],
            'owns_game' => !empty($entitlements['items']),
            'expires_in' => $minecraftAuth['expires_in']
        ];
    }

    private function authenticateXboxLive(string $msAccessToken): array
    {
        $response = $this->httpClient->request('POST', 'https://user.auth.xboxlive.com/user/authenticate', [
            'json' => [
                'Properties' => [
                    'AuthMethod' => 'RPS',
                    'SiteName' => 'user.auth.xboxlive.com',
                    'RpsTicket' => 'd=' . $msAccessToken
                ],
                'RelyingParty' => 'http://auth.xboxlive.com',
                'TokenType' => 'JWT'
            ]
        ]);

        return $response->toArray();
    }

    private function obtainXstsToken(string $xboxToken): string
    {
        $response = $this->httpClient->request('POST', 'https://xsts.auth.xboxlive.com/xsts/authorize', [
            'json' => [
                'Properties' => [
                    'SandboxId' => 'RETAIL',
                    'UserTokens' => [$xboxToken]
                ],
                'RelyingParty' => 'rp://api.minecraftservices.com/',
                'TokenType' => 'JWT'
            ]
        ]);

        $data = $response->toArray();
        return $data['Token'];
    }

    private function loginWithXbox(string $userHash, string $xstsToken): array
    {
        $response = $this->httpClient->request('POST', 'https://api.minecraftservices.com/authentication/login_with_xbox', [
            'json' => [
                'identityToken' => sprintf('XBL3.0 x=%s;%s', $userHash, $xstsToken)
            ]
        ]);

        return $response->toArray();
    }

    private function checkEntitlements(string $minecraftToken): array
    {
        $response = $this->httpClient->request('GET', 'https://api.minecraftservices.com/entitlements/mcstore', [
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraftToken
            ]
        ]);

        return $response->toArray();
    }

    private function getProfile(string $minecraftToken): array
    {
        $response = $this->httpClient->request('GET', 'https://api.minecraftservices.com/minecraft/profile', [
            'headers' => [
                'Authorization' => 'Bearer ' . $minecraftToken
            ]
        ]);

        return $response->toArray();
    }
}
