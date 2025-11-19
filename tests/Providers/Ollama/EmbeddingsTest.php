<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Embedding;
use Tests\Fixtures\FixtureResponse;

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromInput('The food was delicious and the waiter...')
        ->asEmbeddings();

    Http::assertSent(function (Request $request): true {
        expect($request->data()['model'])->toBe('mxbai-embed-large');
        expect($request->data()['input'])->toBe(['The food was delicious and the waiter...']);
        expect($request->data())->not->toHaveKeys(['options']);

        return true;
    });

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-input-1.json'), true);
    $embeddings = array_map(Embedding::fromArray(...), data_get($embeddings, 'embeddings'));

    expect($response->meta->model)->toBe('mxbai-embed-large');
    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(10);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->asEmbeddings();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-file-1.json'), true);
    $embeddings = array_map(Embedding::fromArray(...), data_get($embeddings, 'embeddings'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(512);
});

it('works with multiple embeddings', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-multiple-inputs');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromArray([
            'The food was delicious.',
            'The drinks were not so good',
        ])
        ->asEmbeddings();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-multiple-inputs-1.json'), true);
    $embeddings = array_map(Embedding::fromArray(...), data_get($embeddings, 'embeddings'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->embeddings[1]->embedding)->toBe($embeddings[1]->embedding);
    expect($response->usage->tokens)->toBe(522);
});

describe('Keep alive parameter', function (): void {
    it('includes keep_alive parameter with duration string for embeddings', function (): void {
        FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

        Prism::embeddings()
            ->using(Provider::Ollama, 'mxbai-embed-large')
            ->fromInput('Test input')
            ->withProviderOptions(['keep_alive' => '5m'])
            ->asEmbeddings();

        Http::assertSent(function (Request $request): true {
            $body = $request->data();
            expect($body)->toHaveKey('keep_alive');
            expect($body['keep_alive'])->toBe('5m');

            return true;
        });
    });

    it('includes keep_alive parameter with number for embeddings', function (): void {
        FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

        Prism::embeddings()
            ->using(Provider::Ollama, 'mxbai-embed-large')
            ->fromInput('Test input')
            ->withProviderOptions(['keep_alive' => 300])
            ->asEmbeddings();

        Http::assertSent(function (Request $request): true {
            $body = $request->data();
            expect($body)->toHaveKey('keep_alive');
            expect($body['keep_alive'])->toBe(300);

            return true;
        });
    });

    it('includes keep_alive parameter with negative number for embeddings', function (): void {
        FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

        Prism::embeddings()
            ->using(Provider::Ollama, 'mxbai-embed-large')
            ->fromInput('Test input')
            ->withProviderOptions(['keep_alive' => -1])
            ->asEmbeddings();

        Http::assertSent(function (Request $request): true {
            $body = $request->data();
            expect($body)->toHaveKey('keep_alive');
            expect($body['keep_alive'])->toBe(-1);

            return true;
        });
    });

    it('does not include keep_alive parameter when not provided for embeddings', function (): void {
        FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

        Prism::embeddings()
            ->using(Provider::Ollama, 'mxbai-embed-large')
            ->fromInput('Test input')
            ->asEmbeddings();

        Http::assertSent(function (Request $request): true {
            $body = $request->data();
            expect($body)->not->toHaveKey('keep_alive');

            return true;
        });
    });
});
