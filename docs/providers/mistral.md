# Mistral
## Configuration

```php
'mistral' => [
    'api_key' => env('MISTRAL_API_KEY', ''),
    'url' => env('MISTRAL_URL', 'https://api.mistral.ai/v1'),
],
```
## Provider-specific options

## Audio Processing

Mistral provides advanced speech-to-text capabilities through their Voxtral models, offering state-of-the-art transcription accuracy with native multilingual support and audio understanding features.

### Speech-to-Text

Mistral's Voxtral models deliver exceptional speech recognition performance, outperforming industry standards like Whisper large-v3 across multiple languages and acoustic environments.

#### Basic STT Usage

```php
use Prism\Prism\ValueObjects\Media\Audio;

$audioFile = Audio::fromPath('/path/to/recording.mp3');

$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507')
    ->withInput($audioFile)
    ->asText();

echo "Transcription: " . $response->text;
```

#### Model Selection Guide

Choose the right Voxtral model for your use case:

```php
// For production transcription with highest accuracy
$response = Prism::audio()
    ->using('mistral', 'voxtral-small-latest')
    ->withInput($audioFile)
    ->asText();

// For efficient transcription and edge deployment
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-latest')
    ->withInput($audioFile)
    ->asText();

// For optimized transcription-only service
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507')
    ->withInput($audioFile)
    ->asText();
```

#### Language Detection and Specification

Voxtral automatically detects languages or you can specify them for better accuracy:

```php
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507' 
    ->asText();

// Multilingual support - single model handles multiple languages
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507')
    ->withInput($multilingualAudioFile)
    ->withProviderOptions([
        // Auto-detection works well for mixed-language content
        'temperature' => 0.1,
    ])
    ->asText();
```

#### Timestamps and Segmentation

Get detailed timing information with your transcriptions:

```php
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507')
    ->withInput($audioFile)
    ->withProviderOptions([
        'timestamp_granularities' => ['segment'], // Available: word, segment
        'response_format' => 'json',
    ])
    ->asText();

// Access segment information with timestamps
$segments = $response->additionalContent['segments'] ?? [];
foreach ($segments as $segment) {
    echo "Text: " . $segment['text'] . "\n";
    echo "Start: " . $segment['start'] . "s\n";
    echo "End: " . $segment['end'] . "s\n";
}
```

#### Context and Prompts

Improve transcription accuracy with contextual information:

```php
$response = Prism::audio()
    ->using('mistral', 'voxtral-mini-2507')
    ->withInput($audioFile)
    ->withProviderOptions([
        'prompt' => 'This is a medical consultation discussing patient symptoms and treatment options.',
        'language' => 'en',
        'temperature' => 0.0,         // Deterministic for medical content
    ])
    ->asText();
```

#### Long-form Audio Processing

Voxtral handles extended audio without chunking:

```php
// Process up to 30 minutes of audio in a single request
$longAudioFile = Audio::fromPath('/path/to/long_meeting.wav');

$response = Prism::audio()
    ->using('mistral', 'voxtral-small-latest')
    ->withInput($longAudioFile)
    ->withProviderOptions([
        'timestamp_granularities' => ['segment'],
        'language' => 'en',
    ])
    ->asText();

echo "Full transcription: " . $response->text;

// Access usage information
if ($response->usage) {
    echo "Audio duration: " . $response->usage->promptTokens . " tokens\n";
    echo "Total tokens: " . $response->usage->totalTokens . "\n";
}
```

#### Creating Audio Objects

```php
use Prism\Prism\ValueObjects\Media\Audio;

// From local file path
$audio = Audio::fromPath('/path/to/audio.mp3');

// From remote URL
$audio = Audio::fromUrl('https://example.com/recording.wav');

// From base64 encoded data
$audio = Audio::fromBase64($base64AudioData, 'audio/mpeg');

// From binary content
$audioContent = file_get_contents('/path/to/audio.wav');
$audio = Audio::fromContent($audioContent, 'audio/wav');
```

## Documents
The text generation part of the exposed Facade only allows documents to be passed in through via URL.
See the [documents](./../input-modalities/documents.md) on how to do that.

## OCR
Mistral provides an OCR endpoint which can be used to extract text from documents.
This OCR endpoint can be used like this:

```php
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\Providers\Mistral\Mistral;
use Prism\Prism\Providers\Mistral\ValueObjects\OCRResponse;

/** @var Mistral $provider */
$provider = Prism::provider(\Prism\Prism\Enums\Provider::Mistral);

/** @var OCRResponse $ocrResponse */
$ocrResponse = $provider->ocr(
    'mistral-ocr-latest',
    Document::fromUrl('https://prismphp.com/storage/prism-text-generation.pdf')
);

/**
* Just need the full text of all the pages combined? Use the toText() method.
 */
$text = $ocrResponse->toText();
```
::: tip
The OCR endpoint response time can vary depending on the size of the document. We recommend doing this in the background like a queue with a longer timeout.
:::

