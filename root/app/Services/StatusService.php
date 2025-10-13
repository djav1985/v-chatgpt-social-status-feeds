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
            'facebook', 'google-business' => 512,
            'twitter' => 256,
            'instagram' => 512,
            default => 512,
        };

        $totalTags = match ($platform) {
            'facebook', 'google-business' => '3 to 5',
            'twitter' => '3',
            'instagram' => '20 to 30',
            default => '5 to 10',
        };

        $platformDescription = match ($platform) {
            'facebook', 'google-business' => 'Must stay under 150 characters',
            'twitter' => 'ONE SENTENCE ONLY. NO EXCEPTIONS. Do NOT write more than one sentence or it will be discarded.',
            'instagram' => 'Must stay under 150 characters',
            default => 'Must stay under 150 characters',
        };

        $totalCharacters = match ($platform) {
            'facebook', 'google-business' => 250,
            'twitter' => 100,
            'instagram' => 150,
            default => 250,
        };

        $statusResponse = self::generateSocialStatus(
            $systemMessage,
            $prompt,
            $includeHashtags,
            $platform,
            $platformDescription,
            $totalTags,
            $totalCharacters,
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

        // Platform-specific assembly:
        // - Twitter: omit CTA, order -> status [link] [hashtags]
        // - Google Business: omit CTA but include link -> status [link] [hashtags]
        // - Others: include CTA (if present), then link, then hashtags
        if ($platform === 'twitter') {
            if (!empty($link)) {
                $finalStatus .= ' ' . $link;
            }
        } elseif ($platform === 'google-business') {
            // intentionally left blank here
        } else {
            // Other platforms: include CTA (if provided) then the link
            if ($cta !== '') {
                $finalStatus .= ' ' . $cta;
            }
            if (!empty($link)) {
                $finalStatus .= ' ' . $link;
            }
        }

        if ($includeHashtags && $hashtagsText !== '') {
            $finalStatus .= ' ' . $hashtagsText;
        }

        // Validate final assembled status against Twitter's 280 character limit
        if ($platform === 'twitter') {
            $finalStatusLength = mb_strlen($finalStatus, 'UTF-8');
            if ($finalStatusLength > 280) {
                $error = sprintf(
                    'Final assembled status for %s exceeds Twitter character limit: %d characters (limit: 280). Status: "%s"',
                    $accountName,
                    $finalStatusLength,
                    $finalStatus
                );
                ErrorManager::getInstance()->log($error, 'error');
                return ['error' => $error];
            }
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
    private static function openaiResponsesRequest(array $data = []): array
    {
        $client = self::getClient();
        try {
            $response = $client->responses()->create($data);
            // Simpler conversion: always JSON round-trip the SDK response to
            // produce an associative array. This is robust across SDK return
            // types and keeps downstream code working with arrays.
            $arr = json_decode(json_encode($response), true);
            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            $error = "Failed to make API request to responses endpoint: " . $e->getMessage();
            ErrorManager::getInstance()->log($error, 'error');
            return ["error" => $error];
        }
    }

    /**
     * Generates a structured social media post with an image prompt.
     *
     * @param string $systemMessage The system message for generating content.
     * @param string $prompt The user prompt for generating content.
     * @param bool $includeHashtags Whether to include hashtags in the post.
     * @param string $totalTags The total number of hashtags to include.
     * @param int $statusTokens The number of tokens for the status.
     * @param string $accountName The name of the account.
     * @param string $accountOwner The owner of the account.
     * @return array<string, mixed> Returns the API response containing structured data or an error array.
     */
    private static function generateSocialStatus(string $systemMessage, string $prompt, bool $includeHashtags, string $platform, string $platformDescription, string $totalTags, int $totalCharacters, int $statusTokens, string $accountName, string $accountOwner): array
    {
        // Build JSON schema for responses endpoint
        $jsonSchema = [
            "type" => "object",
            "properties" => [
                "status" => [
                    "type" => "string",
                    "description" => "Post text for $platform. Must be under $totalCharacters characters. Do NOT exceed this limit. $platformDescription."
                ],
                "image_prompt" => [
                    "type" => "string",
                    "description" => "Describe an image idea that matches the status."
                ],
            ],
            // CTA is intentionally not added here for twitter. It will be
            // included in the schema and required list for non-Twitter
            // platforms further below.
            "required" => ["status", "image_prompt"],
            "additionalProperties" => false
        ];

        // Conditionally include CTA for platforms that support it
        // (exclude Twitter and Google Business)
        if ($platform !== 'twitter' && $platform !== 'google-business') {
            $jsonSchema["properties"]["cta"] = [
                "type" => "string",
                "description" => "Short call to action. The link will be added separately."
            ];
            // Ensure CTA is required for platforms that support it
            $jsonSchema["required"][] = "cta";
        }

        if ($includeHashtags) {
            $jsonSchema["properties"]["hashtags"] = [
                "type" => "string",
                "description" => "Space-separated #hashtags, ideally $totalTags."
            ];
            $jsonSchema["required"][] = "hashtags";
        }

        $data = [
            "model" => MODEL,
            "instructions" => $systemMessage,
            "input" => $prompt,
            "max_output_tokens" => $statusTokens,
            "reasoning" => [
                "effort" => "minimal"
            ],
            "text" => [
                "format" => [
                    "type" => "json_schema",
                    "name" => "generate_status",
                    "schema" => $jsonSchema,
                    "strict" => true,
                ],
            ],
        ];

        // API call using OpenAI Responses endpoint
        $response = self::openaiResponsesRequest($data);

        if (isset($response['error'])) {
            $errorMsg = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
            if (strpos($errorMsg, "Error generating status for") === false) {
                $errorMsg = "Error generating status for $accountName owned by $accountOwner: " . $errorMsg;
                ErrorManager::getInstance()->log($errorMsg, 'error');
            }
            return ["error" => $errorMsg];
        }

        // Check if the response is incomplete
        $responseStatus = $response['status'] ?? '';
        if ($responseStatus === 'incomplete') {
            $reason = $response['incomplete_details']['reason'] ?? 'unknown';
            $error = "Error generating status for $accountName owned by $accountOwner: API response incomplete (reason: $reason). This usually means the response was truncated. Please try again.";
            ErrorManager::getInstance()->log($error, 'error');
            return ["error" => $error];
        }

        // Parse OpenAI Responses endpoint output (emoji-friendly & auto-repair)
        $outputArr = $response['output'] ?? [];
        foreach ($outputArr as $outputItem) {
            if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                foreach ($outputItem['content'] as $contentBlock) {
                    if (isset($contentBlock['type']) && $contentBlock['type'] === 'output_text' && isset($contentBlock['text'])) {

                        $raw = $contentBlock['text'];

                        // 1. Remove illegal control characters (but KEEP emojis)
                        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw);

                        // 2. Auto-close broken JSON if truncated
                        $fixed = self::repairTruncatedJson(rtrim($cleaned));

                        // 3. Decode
                        $decodedContent = json_decode($fixed, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $decodedContent;
                        } else {
                            $jsonError = json_last_error_msg();
                            $error = "Error generating status for $accountName owned by $accountOwner: Invalid structured output from API (JSON decode error after clean-up: $jsonError). Original content: " . var_export($raw, true);
                            ErrorManager::getInstance()->log($error, 'error');
                            return ["error" => $error];
                        }
                    }
                }
            }
        }

        $error = "Error generating status for $accountName owned by $accountOwner: API response did not contain expected structured content. Full response: " . var_export($response, true);
        ErrorManager::getInstance()->log($error, 'error');
        return ["error" => $error];
    }

    /**
     * Attempt to repair truncated JSON payloads by appending missing closing braces.
     */
    private static function repairTruncatedJson(string $json): string
    {
        if ($json === '') {
            return $json;
        }

        if (!str_starts_with($json, '{')) {
            return $json;
        }

        // Try to decode first - if it's already valid JSON, don't modify it
        json_decode($json);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // Only attempt repair if JSON is invalid
        $openCount = substr_count($json, '{');
        $closeCount = substr_count($json, '}');

        if ($openCount > $closeCount) {
            $json .= str_repeat('}', $openCount - $closeCount);
        }

        return $json;
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

        $client = self::getClient();
        try {
            $response = $client->images()->create($data);
            $responseArr = method_exists($response, 'toArray') ? $response->toArray() : (array)$response;
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Error generating image for owner "%s" and account "%s": %s',
                $accountOwner,
                $accountName,
                $e->getMessage()
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');
            return ['error' => $errorMessage];
        }

        if (isset($responseArr['error']) || !isset($responseArr['data'][0]['url'])) {
            $errorMessage = sprintf(
                'Error generating image for owner "%s" and account "%s": %s',
                $accountOwner,
                $accountName,
                isset($responseArr['error']) ? $responseArr['error'] : 'No image URL returned.'
            );
            ErrorManager::getInstance()->log($errorMessage, 'error');
            return ['error' => $errorMessage];
        }

        $imageUrl = $responseArr['data'][0]['url'];

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
