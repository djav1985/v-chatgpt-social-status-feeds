<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: status-lib.php
 * Description: Library functions for generating statuses and images.
 * License: MIT
 */

/**
 * Generates a status update and associated image for a given account.
 *
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @return array|null Array with success status and message, or null if failed.
 */
function generateStatus(string $accountName, string $accountOwner): ?array
{
    // Sanitize inputs
    $accountName = filter_var($accountName, FILTER_SANITIZE_SPECIAL_CHARS);
    $accountOwner = filter_var($accountOwner, FILTER_SANITIZE_SPECIAL_CHARS);

    // Get account info
    $systemMessage = SYSTEM_MSG;
    $accountInfo = AccountHandler::getAcctInfo($accountOwner, $accountName);
    $userInfo = UserHandler::getUserInfo($accountOwner);

    if (!$accountInfo || !$userInfo) {
        $error = "Error: Account or user not found for $accountOwner / $accountName";
        ErrorHandler::logMessage($error, 'error');
        return ['success' => false, 'message' => $error];
    }

    $prompt = $accountInfo->prompt;
    $link = $accountInfo->link;
    $hashtags = $accountInfo->hashtags;
    $platform = $accountInfo->platform;

    // Add user profile information to the system message
    $systemMessage .= " You work for " . $userInfo->who . " located in " . $userInfo->where . ". " . $userInfo->what . " Your goal is " . $userInfo->goal . ".";

    // Determine tags & tokens
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

    // Generate status
    $statusResponse = generate_social_status($systemMessage, $prompt, $link, $hashtags, $totalTags, $statusTokens, $accountName, $accountOwner);

    if (isset($statusResponse['error'])) {
        $error = "Status generation failed for $accountName owned by $accountOwner: " . $statusResponse['error'];
        ErrorHandler::logMessage($error, 'error');
        return ['success' => false, 'message' => $error];
    }

    $statusContent = $statusResponse['status'] ?? '';
    $cta = $statusResponse['cta'] ?? '';
    $imagePrompt = $statusResponse['image_prompt'] ?? '';

    // Append CTA if platform is not google-business
    if ($platform !== 'google-business') {
        $statusContent .= ' ' . $cta;
    }

    // Append hashtags if needed
    if ($hashtags && isset($statusResponse['hashtags'])) {
        $statusContent .= ' ' . implode(' ', $statusResponse['hashtags']);
    }

    // Generate image
    $imageResponse = generate_social_image($imagePrompt, $accountName, $accountOwner);
    if (isset($imageResponse['error'])) {
        $error = "Image generation failed for $accountName owned by $accountOwner: " . $imageResponse['error'];
        ErrorHandler::logMessage($error, 'error');
        return ['success' => false, 'message' => $error];
    }

    $imageName = $imageResponse['image_name'];

    // Save final status
    StatusHandler::saveStatus($accountName, $accountOwner, $statusContent, $imageName);

    return ['success' => true, 'message' => 'Status generated successfully'];
}

/**
 * Make an HTTP request to OpenAI API.
 *
 * @param string $endpoint API endpoint to call.
 * @param array|null $data Optional data to send in the request body.
 * @return array API response.
 */
function openai_api_request(string $endpoint, ?array $data = null): array
{
    $url = API_ENDPOINT . $endpoint;
    $headers = [
        "Authorization: Bearer " . API_KEY,
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        $error = curl_error($ch);
        ErrorHandler::logMessage("Failed to make API request to $endpoint: $error", 'error');
        return ["error" => "Failed to make API request to $endpoint: $error"];
    }

    return json_decode($response, true);
}

/**
 * Generate structured social media post with image prompt.
 *
 * @param string $systemMessage The system message for generating content.
 * @param string $prompt The user prompt for generating content.
 * @param string $link The link to be included in the post.
 * @param bool $includeHashtags Whether to include hashtags in the post.
 * @param string $totalTags The total number of hashtags.
 * @param int $statusTokens The number of tokens for the status.
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @return array API response containing structured data.
 */
function generate_social_status(string $systemMessage, string $prompt, string $link, bool $includeHashtags, string $totalTags, int $statusTokens, string $accountName, string $accountOwner): array
{
    // Define the structured response schema
    $jsonSchema = [
        "type" => "object",
        "properties" => [
            "status" => [
                "type" => "string",
                "description" => "A catchy and engaging text for the social media post, ideally between 100-150 characters."
            ],
            "cta" => [
                "type" => "string",
                "description" => "A clear and concise call to action, encouraging users to engage at $link"
            ],
            "image_prompt" => [
                "type" => "string",
                "description" => "Write a prompt to generate an image to go with this status."
            ]
        ],
        "required" => ["status", "cta", "image_prompt"],
        "additionalProperties" => false
    ];

    if ($includeHashtags) {
        $jsonSchema['properties']['hashtags'] = [
            "type" => "array",
            "items" => ["type" => "string"],
            "description" => "A list of relevant hashtags for social media, ideally $totalTags trending tags."
        ];
        $jsonSchema['required'][] = "hashtags";
    }

    // Prepare the API request payload
    $data = [
        "model" => MODEL,
        "messages" => [
            ["role" => "system", "content" => $systemMessage],
            ["role" => "user", "content" => $prompt]
        ],
        "response_format" => [
            "type" => "json_schema",
            "json_schema" => [
                "name" => "generate_post",
                "schema" => $jsonSchema,
                "strict" => true
            ]
        ],
        "max_tokens" => $statusTokens,
        "temperature" => TEMPERATURE
    ];

    // Make the API request
    $response = openai_api_request("/chat/completions", $data);

    if (isset($response['error']) || !isset($response['choices'][0]['message']['content'])) {
        $error = $response['error'] ?? 'Unknown error';
        ErrorHandler::logMessage("Error generating status for $accountName owned by $accountOwner: $error", 'error');
        return ["error" => "Error generating status for $accountName owned by $accountOwner: $error"];
    }

    return json_decode($response['choices'][0]['message']['content'], true) ?? ["error" => "Invalid response format"];
}

/**
 * Generate an image using OpenAI's DALL·E API.
 *
 * @param string $imagePrompt The prompt describing the image.
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @return array Image filename or an error array.
 */
function generate_social_image(string $imagePrompt, string $accountName, string $accountOwner): array
{
    $data = [
        "model" => "dall-e-3",
        "prompt" => $imagePrompt,
        "n" => 1,
        "quality" => "standard",
        "size" => "1792x1024"
    ];

    $response = openai_api_request("/images/generations", $data);

    if (isset($response['error']) || !isset($response['data'][0]['url'])) {
        $error = $response['error'] ?? 'Unknown error';
        ErrorHandler::logMessage("Error generating image for $accountName owned by $accountOwner: $error", 'error');
        return ["error" => "Error generating image for $accountName owned by $accountOwner: $error"];
    }

    $image_url = $response['data'][0]['url'];
    $random_name = uniqid() . '.png';
    $image_path = $_SERVER['DOCUMENT_ROOT'] . "/images/$accountOwner/$accountName/$random_name";

    if (!is_dir(dirname($image_path)) && !mkdir(dirname($image_path), 0777, true) && !is_dir(dirname($image_path))) {
        $error = "Failed to create image directory for $accountOwner / $accountName.";
        ErrorHandler::logMessage($error, 'error');
        return ["error" => $error];
    }

    if (file_put_contents($image_path, file_get_contents($image_url)) === false) {
        $error = "Failed to save image for $accountName owned by $accountOwner.";
        ErrorHandler::logMessage($error, 'error');
        return ["error" => $error];
    }

    return ["image_name" => $random_name];
}
