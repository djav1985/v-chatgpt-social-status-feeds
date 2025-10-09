<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusService.php
 * Description: AI Social Status Generator
 */

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use App\Models\Status;
use App\Core\ErrorManager;
use GuzzleHttp\Client as GuzzleClient;
use InvalidArgumentException;
use OpenAI;
use RuntimeException;

class StatusService
{
    /** @var \OpenAI\Client|null */
    private static $client = null;

    /**
     * Get shared OpenAI client instance.
     */
    private static function getClient(): \OpenAI\Client
    {
        if (self::$client === null) {
            self::$client = OpenAI::factory()
                ->withApiKey(API_KEY)
                ->withBaseUri(rtrim(API_ENDPOINT, '/'))
                ->withHttpClient(new GuzzleClient([
                    'timeout' => 30,
                    'connect_timeout' => 10,
                ]))
                ->make();
        }

        return self::$client;
    }
    /**
     * Generates a status update and associated image for a given account.
     *
     * @param string $accountName The name of the account.
     * @param string $accountOwner The owner of the account.
     * @return array<string, mixed>|null Returns an array with success status and message, or null if the operation fails.
     */
    public static function generateStatus(string $accountName, string $accountOwner): ?array
    {
        $accountName = self::normalizeIdentifier($accountName);
        $accountOwner = self::normalizeIdentifier($accountOwner);

        if ($accountName === '' || $accountOwner === '') {
            $error = 'Account owner and account name are required to generate a status.';
            ErrorManager::getInstance()->log($error, 'error');

            return ['error' => $error];
        }

        $systemMessage = SYSTEM_MSG;
        $accountInfo = Account::getAcctInfo($accountOwner, $accountName);
        $userInfo = User::getUserInfo($accountOwner);
        if (is_array($userInfo)) {
            $userInfo = (object)$userInfo;
        }

        if (!$accountInfo || !$userInfo) {
            $error = sprintf(
                'Error: Account or user not found for owner "%s" and account "%s".',
                $accountOwner,
                $accountName
            );
            ErrorManager::getInstance()->log($error, 'error');

            return ['error' => $error];
        }

        $accountInfo = (object)$accountInfo;
        $prompt = $accountInfo->prompt;
        $link = $accountInfo->link;
        $includeHashtags = (bool) $accountInfo->hashtags;
        $platform = $accountInfo->platform;

        $systemMessage .= sprintf(
            ' You work for %s located in %s. %s Your goal is %s.',
            $userInfo->who,
            $userInfo->where,
            $userInfo->what,
            $userInfo->goal
        );

        $statusTokens = match ($platform) {
            'facebook', 'google-business' => 256,
            'twitter' => 64,
            'instagram' => 128,
            default => 100,
        };

        $totalTags = match ($platform) {
            'facebook', 'google-business' => '3 to 5',
            'twitter' => '3',
            'instagram' => '20 to 30',
            default => '5 to 10',
        };

        $statusResponse = self::generateSocialStatus(
            $systemMessage,
            $prompt,
            $link,
            $includeHashtags,
            $totalTags,
            $statusTokens,
            $accountName,
            $accountOwner
        );

        if (isset($statusResponse['error'])) {
            $errorDetail = is_array($statusResponse['error'])
                ? json_encode($statusResponse['error'])
                : $statusResponse['error'];

            return ['error' => $errorDetail];
        }

        $statusText = trim($statusResponse['status'] ?? '');
        $cta = trim($statusResponse['cta'] ?? '');
        $imagePrompt = trim($statusResponse['image_prompt'] ?? '');
        $hashtagsText = trim($statusResponse['hashtags'] ?? '');

        $finalStatus = $statusText;
        if ($platform !== 'google-business' && $cta !== '') {
            $finalStatus .= ' ' . $cta;
        }
        if ($includeHashtags && $hashtagsText !== '') {
            $finalStatus .= ' ' . $hashtagsText;
        }

        $imageResponse = self::generateSocialImage($imagePrompt, $accountName, $accountOwner);
        if (isset($imageResponse['error'])) {
            return ['error' => $imageResponse['error']];
        }

        $imageName = $imageResponse['image_name'];
        Status::saveStatus($accountName, $accountOwner, $finalStatus, $imageName);

        return ['success' => true];
    }

/**
 * Makes an HTTP request to the OpenAI API.
 *
 * @param string $endpoint The API endpoint to call.
 * @param array<string, mixed> $data Optional data to send in the request body.
 * @return array<string, mixed> Returns the API response as an associative array.
 */
    private static function openaiApiRequest(string $endpoint, array $data = []): array
    {
        $client = self::getClient();

        try {
            switch ($endpoint) {
                case 'chat':
                    $response = $client->chat()->create($data);
                    break;
                case 'images':
                    $response = $client->images()->create($data);
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported endpoint');
            }

            return $response->toArray();
        } catch (\Throwable $e) {
            $error = "Failed to make API request to $endpoint: " . $e->getMessage();
            ErrorManager::getInstance()->log($error, 'error');
            return ["error" => $error];
        }
    }

/**
 * Generates a structured social media post with an image prompt.
 *
 * @param string $systemMessage The system message for generating content.
 * @param string $prompt The user prompt for generating content.
 * @param string $link The link to be included in the post.
 * @param bool $includeHashtags Whether to include hashtags in the post.
 * @param string $totalTags The total number of hashtags to include.
 * @param int $statusTokens The number of tokens for the status.
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @return array<string, mixed> Returns the API response containing structured data or an error array.
 */
    private static function generateSocialStatus(string $systemMessage, string $prompt, string $link, bool $includeHashtags, string $totalTags, int $statusTokens, string $accountName, string $accountOwner): array
{
    // Build JSON Schema
    $jsonSchema = [
                   "type"                 => "object",
                   "properties"           => [
                                              "status"       => [
                                                                 "type"        => "string",
                                                                 "description" => "A catchy and engaging text for the social media post, ideally between 100-150 characters.",
                                                                ],
                                              "cta"          => [
                                                                 "type"        => "string",
                                                                 "description" => "A clear and concise call to action, encouraging users to engage at $link",
                                                                ],
                                              "image_prompt" => [
                                                                 "type"        => "string",
                                                                 "description" => "Write a prompt to generate an image to go with this status.",
                                                                ],
                                             ],
                   "required"             => [
                                              "status",
                                              "cta",
                                              "image_prompt",
                                             ],
                   "additionalProperties" => false,
                  ];

    if ($includeHashtags) {
        $jsonSchema['properties']['hashtags'] = [
                                                 "type"        => "string",
                                                 "description" => "A space-separated string of relevant hashtags for social media, ideally $totalTags trending tags.",
                                                ];
        $jsonSchema['required'][] = "hashtags";
    }

    // Prepare the chat/completions request with json_schema
    $data = [
             "model"           => MODEL,
             "messages"        => [
                                   [
                                    "role"    => "system",
                                    "content" => $systemMessage,
                                   ],
                                   [
                                    "role"    => "user",
                                    "content" => $prompt,
                                   ],
                                  ],
             "response_format" => [
                                   "type"        => "json_schema",
                                   "json_schema" => [
                                                     "name"   => "generate_status",
                                                     "schema" => $jsonSchema,
                                                     "strict" => true,
                                                    ],
                                  ],
             "max_tokens"      => $statusTokens,
             "temperature"     => TEMPERATURE,
            ];

    // API call using OpenAI client
    $response = self::openaiApiRequest('chat', $data);

    if (isset($response['error'])) {
        // Error already logged by openaiApiRequest if it originated there
        $errorMsg = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
        // If the error isn't already specific, make it more specific here
        if (strpos($errorMsg, "Error generating status for") === false) {
             $errorMsg = "Error generating status for $accountName owned by $accountOwner: " . $errorMsg;
             ErrorManager::getInstance()->log($errorMsg, 'error');
        }
        return ["error" => $errorMsg];
    }

    if (!isset($response['choices'][0]['message']['content'])) {
        $error = "Error generating status for $accountName owned by $accountOwner: API response did not contain expected content.";
        ErrorManager::getInstance()->log($error, 'error');
        return ["error" => $error];
    }

    $decodedContent = json_decode($response['choices'][0]['message']['content'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonError = json_last_error_msg();
        $error = "Error generating status for $accountName owned by $accountOwner: Invalid structured output from API (JSON decode error: $jsonError). Original content: " . $response['choices'][0]['message']['content'];
        ErrorManager::getInstance()->log($error, 'error');
        return ["error" => $error];
    }

    return $decodedContent;
}

/**
 * Generates an image using OpenAI's DALL·E API.
 *
 * @param string $imagePrompt The prompt describing the image to generate.
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @return array<string, mixed> Returns an array containing the image filename or an error message.
 */
    private static function generateSocialImage(string $imagePrompt, string $accountName, string $accountOwner): array
    {
        $data = [
            'model' => 'dall-e-3',
            'prompt' => $imagePrompt,
            'n' => 1,
            'quality' => 'standard',
            'size' => '1792x1024',
        ];

        $response = self::openaiApiRequest('images', $data);

        if (isset($response['error']) || !isset($response['data'][0]['url'])) {
            $errorMessage = sprintf(
                'Error generating image for owner "%s" and account "%s": %s',
                $accountOwner,
                $accountName,
                $response['error'] ?? 'Unknown error'
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');

            return ['error' => $errorMessage];
        }

        $imageUrl = $response['data'][0]['url'];

        try {
            $imageDirectory = self::buildImageDirectory($accountOwner, $accountName);
            $fileName = self::generateImageFilename();
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $errorMessage = sprintf(
                'Failed to prepare image storage for owner "%s" and account "%s": %s',
                $accountOwner,
                $accountName,
                $exception->getMessage()
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');

            return ['error' => $errorMessage];
        }

        $dirMode = defined('DIR_MODE') ? DIR_MODE : 0755;
        if (!is_dir($imageDirectory) && !mkdir($imageDirectory, $dirMode, true) && !is_dir($imageDirectory)) {
            $errorMessage = sprintf(
                'Failed to create directory for owner "%s" and account "%s".',
                $accountOwner,
                $accountName
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');

            return ['error' => $errorMessage];
        }

        $imagePath = $imageDirectory . DIRECTORY_SEPARATOR . $fileName;

        $imageContent = @file_get_contents($imageUrl);
        if ($imageContent === false || $imageContent === '') {
            $errorMessage = sprintf(
                'Failed to download image for owner "%s" and account "%s".',
                $accountOwner,
                $accountName
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');

            return ['error' => $errorMessage];
        }

        if (file_put_contents($imagePath, $imageContent) === false) {
            $errorMessage = sprintf(
                'Failed to save image for owner "%s" and account "%s".',
                $accountOwner,
                $accountName
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');

            return ['error' => $errorMessage];
        }

        return ['image_name' => $fileName];
    }

    /**
     * Normalize incoming account identifiers for lookups.
     */
    private static function normalizeIdentifier(string $value): string
    {
        return trim($value);
    }

    /**
     * Build the directory path used to store generated images.
     */
    private static function buildImageDirectory(string $accountOwner, string $accountName): string
    {
        $ownerSegment = self::sanitizePathSegment($accountOwner);
        $accountSegment = self::sanitizePathSegment($accountName);

        return __DIR__ . '/../../public/images/' . $ownerSegment . '/' . $accountSegment;
    }

    /**
     * Sanitize user-controlled values before using them in filesystem paths.
     *
     * @throws InvalidArgumentException When the resulting path segment would be empty.
     */
    private static function sanitizePathSegment(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Path segment cannot be empty.');
        }

        $normalized = str_replace('\\', '/', $trimmed);
        $sanitized = basename($normalized);

        if ($sanitized === '') {
            throw new InvalidArgumentException('Path segment cannot be empty.');
        }

        return $sanitized;
    }

    /**
     * Generate a random filename for an image.
     *
     * @throws RuntimeException When a secure random filename cannot be generated.
     */
    private static function generateImageFilename(): string
    {
        try {
            return bin2hex(random_bytes(16)) . '.png';
        } catch (\Throwable $throwable) {
            throw new RuntimeException('Unable to generate image filename.', 0, $throwable);
        }
    }
}
