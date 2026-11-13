<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedRequestTest.php
 * Description: Shared PHPUnit tests for Request.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use Tests\Support\SharedRequestFactory;
use PHPUnit\Framework\TestCase;

final class SharedRequestTest extends TestCase
{
    public function testTestFactoryBuildsPsr7RequestData(): void
    {
        $request = SharedRequestFactory::create(
            'POST',
            '/login',
            ['next' => '/home'],
            ['username' => 'alice'],
            ['HTTPS' => 'on']
        );

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/login', $request->getUri()->getPath());
        $this->assertSame(['next' => '/home'], $request->getQueryParams());
        $this->assertSame(['username' => 'alice'], $request->getParsedBody());
        $this->assertSame(['HTTPS' => 'on'], $request->getServerParams());
        $this->assertSame('Grace', $request->withParsedBody(['username' => 'Grace'])->getParsedBody()['username']);
        $this->assertSame('alice', $request->getParsedBody()['username']);
    }

    public function testFromGlobalsSeparatesPathFromQueryString(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;

        try {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['REQUEST_URI'] = '/login?next=%2Fhome';
            $_GET = ['next' => '/home'];
            $_POST = ['username' => 'alice'];

            $request = Request::fromGlobals();

            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('/login', $request->getUri()->getPath());
            $this->assertSame(['next' => '/home'], $request->getQueryParams());
            $this->assertSame(['username' => 'alice'], $request->getParsedBody());
            $this->assertSame($_SERVER, $request->getServerParams());
        } finally {
            $_SERVER = $originalServer;
            $_GET = $originalGet;
            $_POST = $originalPost;
        }
    }

}
