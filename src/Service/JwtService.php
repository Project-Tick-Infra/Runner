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

class JwtService
{
    private string $secret;

    public function __construct(string $secret = 'tick_id_secret_key_change_me_in_env')
    {
        $this->secret = $secret;
    }

    public function generateToken(array $payload, int $expiry = 3600): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        $payload_encoded = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload_encoded));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret, true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if (!hash_equals($base64Signature, $validSignature)) return null;

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }
}
