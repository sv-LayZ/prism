<?php

declare(strict_types=1);

namespace Prism\Prism\Providers\Mistral\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Prism\Prism\Audio\SpeechToTextRequest;
use Prism\Prism\Audio\TextResponse;
use Prism\Prism\Providers\Mistral\Concerns\ProcessRateLimits;
use Prism\Prism\ValueObjects\Usage;

class Audio
{
    use ProcessRateLimits;

    public function __construct(protected PendingRequest $client) {}

    public function handleSpeechToText(SpeechToTextRequest $request): TextResponse
    {
        $payload = Arr::whereNotNull([
            'model' => $request->model(),
            'language' => $request->providerOptions('language') ?? null,
            'prompt' => $request->providerOptions('prompt') ?? null,
            'response_format' => $request->providerOptions('response_format') ?? null,
            'temperature' => $request->providerOptions('temperature') ?? null,
            'timestamp_granularities' => $request->providerOptions('timestamp_granularities') ?? null,
        ]);

        // Si c'est une URL, l'envoyer directement au lieu de télécharger le contenu
        if ($request->input()->isUrl()) {
            // Note: Cette approche suppose que l'API Mistral supporte l'envoi direct d'URLs
            // Si l'API ne le supporte pas, il faudra revenir à l'approche multipart
            $payload['file'] = $request->input()->url();
            $response = $this->client->post('audio/transcriptions', $payload);
        } else {
            $response = $this
                ->client
                ->attach(
                    'file',
                    $request->input()->resource(),
                    'audio',
                    ['Content-Type' => $request->input()->mimeType()]
                )
                ->post('audio/transcriptions', $payload);
        }

        if (json_validate($response->body())) {
            $data = $response->json();

            if (! $response->successful()) {
                throw new \Exception('Failed to transcribe audio: '.$response->body());
            }

            return new TextResponse(
                text: $data['text'] ?? '',
                usage: isset($data['usage'])
                    ? new Usage(
                        promptTokens: $data['usage']['prompt_tokens'] ?? 0,
                        completionTokens: $data['usage']['completion_tokens'] ?? 0,
                    )
                    : null,
                additionalContent: $data,
            );
        }

        // Handle other response formats like vtt
        return new TextResponse(
            text: $response->body(),
        );
    }
}
