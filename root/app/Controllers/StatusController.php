<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use Exception;
use App\Models\Account;
use App\Models\User;
use App\Models\Feed;
use App\Core\ErrorMiddleware;
use App\Core\Utility;


/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusController.php
 * Description: AI Social Status Generator
 */



class StatusController
{
    /**
     * Generates a status update and associated image for a given account.
     *
     * @param string $accountName The name of the account.
     * @param string $accountOwner The owner of the account.
     * @return array|null Returns an array with success status and message, or null if the operation fails.
     */
    public static function generateStatus(string $accountName, string $accountOwner): ?array
    {
    $accountName = htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8');
    $accountOwner = htmlspecialchars($accountOwner, ENT_QUOTES, 'UTF-8');

    $systemMessage = SYSTEM_MSG;
    $accountInfo = Account::getAcctInfo($accountOwner, $accountName);
    $userInfo = User::getUserInfo($accountOwner);

    if (!$accountInfo || !$userInfo) {
        $error = "Error: Account or user not found for $accountOwner / $accountName";
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    $prompt = $accountInfo->prompt;
    $link = $accountInfo->link;
    $includeHashtags = $accountInfo->hashtags;
    $platform = $accountInfo->platform;

    $systemMessage .= " You work for " . $userInfo->who . " located in " . $userInfo->where . ". " . $userInfo->what . " Your goal is " . $userInfo->goal . ".";

    $statusTokens = match ($platform) {
        'facebook', 'google-business' => 256,
        'twitter' => 64,
        'instagram' => 128,
        default => 100
    };

    $totalTags = match ($platform) {
        'facebook', 'google-business' => '3 to 5',
        'twitter' => '3',
        'instagram' => '20 to 30',
        default => '5 to 10'
    };

    // Generate status using structured output
    $statusResponse = self::generate_social_status(
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
        $errorDetail = is_array($statusResponse['error']) ? json_encode($statusResponse['error']) : $statusResponse['error'];
        $error = "Status generation failed for $accountName owned by $accountOwner: $errorDetail";
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    $statusText = trim($statusResponse['status'] ?? '');
    $cta = trim($statusResponse['cta'] ?? '');
    $imagePrompt = trim($statusResponse['image_prompt'] ?? '');
    $hashtagsText = trim($statusResponse['hashtags'] ?? '');

    $finalStatus = $statusText;
    if ($platform !== 'google-business' && $cta) {
        $finalStatus .= ' ' . $cta;
    }
    if ($includeHashtags && $hashtagsText) {
        $finalStatus .= ' ' . $hashtagsText;
    }

    $imageResponse = self::generate_social_image($imagePrompt, $accountName, $accountOwner);
    if (isset($imageResponse['error'])) {
        $error = "Image generation failed for $accountName owned by $accountOwner: " . $imageResponse['error'];
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    $imageName = $imageResponse['image_name'];
    Feed::saveStatus($accountName, $accountOwner, $finalStatus, $imageName);

    return ['success' => true];
}

/**
 * Makes an HTTP request to the OpenAI API.
 *
 * @param string $endpoint The API endpoint to call.
 * @param array|null $data Optional data to send in the request body.
 * @return array Returns the API response as an associative array.
 */
    private static function openaiApiRequest(string $endpoint, ?array $data = null): array
{
    // Ensure there is exactly one slash between the endpoint base and path
    $url = rtrim(API_ENDPOINT, '/') . '/' . ltrim($endpoint, '/');
    $headers = [
                "Authorization: Bearer " . API_KEY,
                "Content-Type: application/json",
               ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    // Prevent hanging requests
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($response === false || $statusCode < 200 || $statusCode >= 300) {
        if ($response === false) {
            $error = "Failed to make API request to $endpoint: $error";
        } else {
            $error = "API request to $endpoint returned HTTP status $statusCode";
        }
        ErrorMiddleware::logMessage($error, 'error');
        curl_close($ch);
        return ["error" => $error];
    }

    curl_close($ch);

    return json_decode($response, true);
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
 * @return array Returns the API response containing structured data or an error array.
 */
    private static function generate_social_status(string $systemMessage, string $prompt, string $link, bool $includeHashtags, string $totalTags, int $statusTokens, string $accountName, string $accountOwner): array
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

    // API call to /v1/chat/completions
    $response = self::openaiApiRequest("/chat/completions", $data);

    if (isset($response['error'])) {
        // Error already logged by openaiApiRequest if it originated there
        $errorMsg = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
        // If the error isn't already specific, make it more specific here
        if (strpos($errorMsg, "Error generating status for") === false) {
             $errorMsg = "Error generating status for $accountName owned by $accountOwner: " . $errorMsg;
             ErrorMiddleware::logMessage($errorMsg, 'error');
        }
        return ["error" => $errorMsg];
    }

    if (!isset($response['choices'][0]['message']['content'])) {
        $error = "Error generating status for $accountName owned by $accountOwner: API response did not contain expected content.";
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    $decodedContent = json_decode($response['choices'][0]['message']['content'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonError = json_last_error_msg();
        $error = "Error generating status for $accountName owned by $accountOwner: Invalid structured output from API (JSON decode error: $jsonError). Original content: " . $response['choices'][0]['message']['content'];
        ErrorMiddleware::logMessage($error, 'error');
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
 * @return array Returns an array containing the image filename or an error message.
 */
    private static function generate_social_image(string $imagePrompt, string $accountName, string $accountOwner): array
{
    $data = [
             "model"   => "dall-e-3",
             "prompt"  => $imagePrompt,
             "n"       => 1,
             "quality" => "standard",
             "size"    => "1792x1024",
            ];

    $response = self::openaiApiRequest("/images/generations", $data);

    if (isset($response['error']) || !isset($response['data'][0]['url'])) {
        $error = "Error generating image for $accountName owned by $accountOwner: " . ($response['error'] ?? 'Unknown error');
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    $image_url = $response['data'][0]['url'];
    $random_name = uniqid() . '.png';
    // Save under root/public/images/<owner>/<account>/
    $image_path = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName . '/' . $random_name;

    $dirMode = defined('DIR_MODE') ? DIR_MODE : 0755;
    if (!is_dir(dirname($image_path)) && !mkdir(dirname($image_path), $dirMode, true)) {
        $error = "Failed to create directory for $accountName owned by $accountOwner.";
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    if (file_put_contents($image_path, file_get_contents($image_url)) === false) {
        $error = "Failed to save image for $accountName owned by $accountOwner.";
        ErrorMiddleware::logMessage($error, 'error');
        return ["error" => $error];
    }

    return ["image_name" => $random_name];
}

}
