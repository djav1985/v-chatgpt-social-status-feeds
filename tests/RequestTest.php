<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testConstructorStoresRequestData(): void
    {
        $request = new Request('POST', '/login', ['next' => '/home'], ['username' => 'alice'], ['HTTPS' => 'on']);

        $this->assertSame('POST', $request->method);
        $this->assertSame('/login', $request->path);
        $this->assertSame(['next' => '/home'], $request->get);
        $this->assertSame(['username' => 'alice'], $request->post);
        $this->assertSame(['HTTPS' => 'on'], $request->server);
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

            $this->assertSame('POST', $request->method);
            $this->assertSame('/login', $request->path);
            $this->assertSame(['next' => '/home'], $request->get);
            $this->assertSame(['username' => 'alice'], $request->post);
            $this->assertSame($_SERVER, $request->server);
        } finally {
            $_SERVER = $originalServer;
            $_GET = $originalGet;
            $_POST = $originalPost;
        }
    }

    public function testFromGlobalsUsesSafeDefaultsForMissingRequestUri(): void
    {
        $originalServer = $_SERVER;

        try {
            unset($_SERVER['REQUEST_URI']);
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = Request::fromGlobals();

            $this->assertSame('GET', $request->method);
            $this->assertSame('/', $request->path);
        } finally {
            $_SERVER = $originalServer;
        }
    }
}
