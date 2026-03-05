<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Tests;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor / accessors
    // -------------------------------------------------------------------------

    public function testDefaultStatusCodeIs200(): void
    {
        $response = new Response();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDefaultBodyIsEmpty(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getBody());
    }

    public function testDefaultHeadersAreEmpty(): void
    {
        $response = new Response();

        $this->assertSame([], $response->getHeaders());
    }

    public function testCustomStatusCodeIsStored(): void
    {
        $response = new Response(404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testKnownStatusCodeResolvesReasonPhrase(): void
    {
        $response = new Response(404);

        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testUnknownStatusCodeHasEmptyReasonPhrase(): void
    {
        $response = new Response(999);

        $this->assertSame('', $response->getReasonPhrase());
    }

    public function testCustomReasonPhraseOverridesDefault(): void
    {
        $response = new Response(200, [], '', 'Everything Is Fine');

        $this->assertSame('Everything Is Fine', $response->getReasonPhrase());
    }

    public function testConstructorStoresBody(): void
    {
        $response = new Response(200, [], 'Hello World');

        $this->assertSame('Hello World', $response->getBody());
    }

    public function testConstructorStoresHeaders(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/html']);

        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    public function testConstructorStoresHeaderListValues(): void
    {
        $response = new Response(200, ['Set-Cookie' => ['a=1', 'b=2']]);

        $this->assertSame(['a=1', 'b=2'], $response->getHeader('Set-Cookie'));
    }

    // -------------------------------------------------------------------------
    // Header helpers
    // -------------------------------------------------------------------------

    public function testHasHeaderReturnsTrueWhenPresent(): void
    {
        $response = new Response(200, ['X-Foo' => 'bar']);

        $this->assertTrue($response->hasHeader('X-Foo'));
    }

    public function testHasHeaderReturnsFalseWhenAbsent(): void
    {
        $response = new Response();

        $this->assertFalse($response->hasHeader('X-Foo'));
    }

    public function testHasHeaderIsCaseInsensitive(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/html']);

        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertTrue($response->hasHeader('CONTENT-TYPE'));
    }

    public function testGetHeaderIsCaseInsensitive(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json']);

        $this->assertSame(['application/json'], $response->getHeader('content-type'));
    }

    public function testGetHeaderReturnsEmptyArrayWhenAbsent(): void
    {
        $response = new Response();

        $this->assertSame([], $response->getHeader('X-Missing'));
    }

    public function testGetHeaderLineJoinsMultipleValues(): void
    {
        $response = new Response(200, ['Accept' => ['text/html', 'application/json']]);

        $this->assertSame('text/html, application/json', $response->getHeaderLine('Accept'));
    }

    public function testGetHeaderLineReturnsEmptyStringWhenAbsent(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getHeaderLine('X-Missing'));
    }

    // -------------------------------------------------------------------------
    // Immutable mutators
    // -------------------------------------------------------------------------

    public function testWithStatusReturnsNewInstance(): void
    {
        $original = new Response(200);
        $modified = $original->withStatus(404);

        $this->assertNotSame($original, $modified);
        $this->assertSame(200, $original->getStatusCode());
        $this->assertSame(404, $modified->getStatusCode());
    }

    public function testWithStatusUpdatesReasonPhrase(): void
    {
        $response = (new Response(200))->withStatus(500);

        $this->assertSame('Internal Server Error', $response->getReasonPhrase());
    }

    public function testWithStatusAcceptsCustomReasonPhrase(): void
    {
        $response = (new Response(200))->withStatus(418, "I'm a teapot");

        $this->assertSame("I'm a teapot", $response->getReasonPhrase());
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $original = new Response();
        $modified = $original->withHeader('X-Foo', 'bar');

        $this->assertNotSame($original, $modified);
        $this->assertFalse($original->hasHeader('X-Foo'));
        $this->assertTrue($modified->hasHeader('X-Foo'));
    }

    public function testWithHeaderReplacesExistingValue(): void
    {
        $response = (new Response(200, ['X-Foo' => 'old']))->withHeader('X-Foo', 'new');

        $this->assertSame(['new'], $response->getHeader('X-Foo'));
    }

    public function testWithHeaderAcceptsArrayValue(): void
    {
        $response = (new Response())->withHeader('Set-Cookie', ['a=1', 'b=2']);

        $this->assertSame(['a=1', 'b=2'], $response->getHeader('Set-Cookie'));
    }

    public function testWithAddedHeaderAppendsValue(): void
    {
        $response = (new Response(200, ['X-Foo' => 'first']))->withAddedHeader('X-Foo', 'second');

        $this->assertSame(['first', 'second'], $response->getHeader('X-Foo'));
    }

    public function testWithAddedHeaderCreatesHeaderWhenAbsent(): void
    {
        $response = (new Response())->withAddedHeader('X-Foo', 'value');

        $this->assertSame(['value'], $response->getHeader('X-Foo'));
    }

    public function testWithoutHeaderRemovesHeader(): void
    {
        $response = (new Response(200, ['X-Foo' => 'bar']))->withoutHeader('X-Foo');

        $this->assertFalse($response->hasHeader('X-Foo'));
    }

    public function testWithoutHeaderDoesNothingWhenAbsent(): void
    {
        $response = (new Response())->withoutHeader('X-Missing');

        $this->assertSame([], $response->getHeaders());
    }

    public function testWithBodyReturnsNewInstance(): void
    {
        $original = new Response(200, [], 'old');
        $modified = $original->withBody('new');

        $this->assertNotSame($original, $modified);
        $this->assertSame('old', $original->getBody());
        $this->assertSame('new', $modified->getBody());
    }

    // -------------------------------------------------------------------------
    // Static factories
    // -------------------------------------------------------------------------

    public function testRedirectFactoryDefaultsTo302(): void
    {
        $response = Response::redirect('/home');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testRedirectFactoryAcceptsCustomStatusCode(): void
    {
        $response = Response::redirect('/home', 301);

        $this->assertSame(301, $response->getStatusCode());
    }

    public function testTextFactorySetsContentType(): void
    {
        $response = Response::text('hello');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('hello', $response->getBody());
    }

    public function testHtmlFactorySetsContentType(): void
    {
        $response = Response::html('<p>hi</p>');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('<p>hi</p>', $response->getBody());
    }

    public function testJsonFactoryEncodesData(): void
    {
        $response = Response::json(['key' => 'value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"key":"value"}', $response->getBody());
    }

    public function testJsonFactoryAcceptsCustomStatusCode(): void
    {
        $response = Response::json(['error' => 'not found'], 404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testJsonFactoryThrowsOnInvalidData(): void
    {
        $this->expectException(\JsonException::class);

        // NAN cannot be JSON-encoded and will trigger JSON_THROW_ON_ERROR
        Response::json(['value' => NAN]);
    }

    public function testJsonFactoryThrowsEvenWithoutExplicitThrowFlag(): void
    {
        $this->expectException(\JsonException::class);

        // Passing 0 as flags should still throw because JSON_THROW_ON_ERROR is
        // OR-ed in unconditionally inside the factory.
        Response::json(['value' => NAN], 200, 0);
    }

    // -------------------------------------------------------------------------
    // Chaining
    // -------------------------------------------------------------------------

    public function testChainingMultipleMutators(): void
    {
        $response = (new Response())
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json')
            ->withBody('{"ok":true}');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"ok":true}', $response->getBody());
    }

    // -------------------------------------------------------------------------
    // send() — capture output to verify behaviour
    // -------------------------------------------------------------------------

    public function testSendEmitsBody(): void
    {
        $response = new Response(200, [], 'body content');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('body content', $output);
    }

    public function testSendEmitsEmptyBodySilently(): void
    {
        $response = new Response();

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
