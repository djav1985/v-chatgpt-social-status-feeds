<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../lib/status-lib.php
 * Description: ChatGPT API Status Generator
 */

function generateStatus($accountName, $accountOwner)
{
    // Use the predefined system message constant for generating statuses
    $system_message = SYSTEM_MSG;

    // Retrieve account information from the database using the account owner's and name's details
    $accountInfo = getAcctInfo($accountOwner, $accountName);

    // Extract relevant data from account information
    $prompt = $accountInfo->prompt;         // The prompt used for generating the status update
    $link = $accountInfo->link;             // Link to be included in the status
    $hashtags = $accountInfo->hashtags;     // Hashtags that may be appended to the status
    $image_prompt = $accountInfo->image_prompt; // Image prompt for generating an associated image
    $platform = $accountInfo->platform;     // Platform type (e.g., Facebook, Twitter)

    // Call the function to retrieve the status content from the API based on provided info
    list($status_content, $status_prompt_tokens, $status_completion_tokens) = getStatus($link, $prompt, $platform, $system_message);
    updateTokens($accountName, $accountOwner, $status_prompt_tokens, $status_completion_tokens);

    // Generate the image prompt based on the status content and initial image prompt
    list($generated_image_prompt, $image_prompt_tokens, $image_completion_tokens) = getImagePrompt($status_content, $image_prompt);
    updateTokens($accountName, $accountOwner, $image_prompt_tokens, $image_completion_tokens);

    // Attempt to generate the actual image associated with the status
    $attempts = 0;
    $max_attempts = 5;
    $image_name = '';

    while (empty($image_name) && $attempts < $max_attempts) {
        $image_name = getImage($accountName, $accountOwner, $generated_image_prompt);

        if (!empty($image_name)) {
            // Update cost in the database if image is generated successfully
            updateCost($accountName, $accountOwner);
        } else {
            // Log the failure and retry attempt
            error_log("Image generation failed for $accountOwner on account $accountName. Attempt #$attempts.");
            // Update image_retries count in the database if image generation fails
            updateImageRetries($accountName, $accountOwner);
            list($generated_image_prompt, $image_prompt_tokens, $image_completion_tokens) = getImagePrompt($status_content, $image_prompt);
            $attempts++;
        }
    }

    if (empty($image_name)) {
        exit('Image generation failed after maximum retries.');
    }

    // Conditionally append hashtags to the status content if they are requested
    if ($hashtags) {
        // Get appropriate hashtags based on the status content and platform being used
        $hashtag_content = getHashtags($status_content, $platform); // Adjust number of hashtags based on platform
        // Append the retrieved hashtags to the status content
        $status_content .= ' ' . $hashtag_content;
    }

    // Save the final generated status along with the associated image name in the database
    saveStatus($accountName, $accountOwner, $status_content, $image_name);
}

function getStatus($link, $prompt, $platform, $system_message)
{
    // Determine the maximum token limit based on the specified platform
    if ($platform === 'facebook') {
        $statusTokens = 256; // Token limit for Facebook
    } elseif ($platform === 'twitter') {
        $statusTokens = 64;  // Token limit for Twitter
    } elseif ($platform === 'instagram') {
        $statusTokens = 128; // Token limit for Instagram
    }

    // Prepare the data for the API request to generate a status update
    $status_data = [
        'model' => MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => 'Create a compelling status update ' . $prompt . ' Keep it under 256 characters with NO hashtags and add the following CTA: Visit: ' . $link]
        ],
        'temperature' => TEMPERATURE,
        'max_tokens' => $statusTokens,
    ];

    // Execute API request
    $status_response = executeApiRequest(API_ENDPOINT, $status_data);

    // Check if the API call was unsuccessful
    if ($status_response === false) {
        exit; // Return an error message if the request failed
    }

    // Decode the JSON response to extract the generated status update
    $status_response_data = json_decode($status_response, true);

    $prompt_tokens = $status_response_data['usage']['prompt_tokens'] ?? 0;
    $completion_tokens = $status_response_data['usage']['completion_tokens'] ?? 0;

    return [$status_response_data['choices'][0]['message']['content'] ?? '', $prompt_tokens, $completion_tokens];
}

function getImagePrompt($status_content, $image_prompt)
{
    // Prepare the data for the API request to generate an image prompt
    $prompt_data = [
        'model' => MODEL,
        'messages' => [
            ['role' => 'system', 'content' => 'Your job is to write a G-rated image generation prompt based on a status update that aligns with content policies. Ensure the prompt excludes any elements of violence, gore, explicit or adult content, hate speech, harmful themes or illegal activities. The prompt should focus on safe, respectful themes suitable for a broad audience. (Do not comment before or after the prompt)'],
            ['role' => 'user', 'content' => 'Based on the following status, write an image generation prompt: ' . $status_content . ' Include instructions to: ' . $image_prompt]
        ],
        'temperature' => TEMPERATURE,
        'max_tokens' => 256, // Limit the response size to 256 tokens
    ];

    // Execute API request
    $prompt_response = executeApiRequest(API_ENDPOINT, $prompt_data);

    // Check if the API call was unsuccessful
    if ($prompt_response === false) {
        exit; // Return an error message if the request failed
    }

    // Decode the JSON response to extract the generated image prompt
    $prompt_response_data = json_decode($prompt_response, true);

    $prompt_tokens = $prompt_response_data['usage']['prompt_tokens'] ?? 0;
    $completion_tokens = $prompt_response_data['usage']['completion_tokens'] ?? 0;

    return [$prompt_response_data['choices'][0]['message']['content'] ?? '', $prompt_tokens, $completion_tokens];
}

function getImage($accountName, $accountOwner, $generated_image_prompt)
{
    // Prepare the data for the API to generate the image
    $image_data = [
        'model' => 'dall-e-3',          // Specify the model to use for image generation
        'prompt' => $generated_image_prompt, // The prompt for generating the image
        'n' => 1,                         // Number of images to generate
        'quality' => "standard",         // Quality setting for the image
        'size' => "1792x1024"            // Dimensions of the generated image
    ];

    // Execute API request
    $image_response = executeApiRequest('https://api.openai.com/v1/images/generations', $image_data);

    // Decode the JSON response to extract the image URL
    $image_response_data = json_decode($image_response, true);
    $image_url = $image_response_data['data'][0]['url'] ?? ''; // Get the URL of the generated image

    // If an image URL exists, save the image locally
    if (!empty($image_url)) {
        $random_name = uniqid() . '.png'; // Generate a unique name for the image
        $image_path = __DIR__ . '/../public/images/' . $accountOwner . '/' . $accountName . '/' . $random_name; // Define the path for saving the image
        file_put_contents($image_path, file_get_contents($image_url)); // Save the image to the specified path
        return $random_name; // Return the name of the saved image
    }
    return ''; // Return an empty string if no image URL was found
}

function getHashtags($status_content, $platform)
{
    // Determine the number of hashtags and tokens based on the platform
    if ($platform === 'facebook') {
        $totaltags = '3 to 5';       // Total tags allowed for Facebook
        $hashtagTokens = 60;         // Maximum tokens for response
    } elseif ($platform === 'twitter') {
        $totaltags = '3';            // Total tags allowed for Twitter
        $hashtagTokens = 30;         // Maximum tokens for response
    } elseif ($platform === 'instagram') {
        $totaltags = '20 to 30';     // Total tags allowed for Instagram
        $hashtagTokens = 128;        // Maximum tokens for response
    }

    // Prepare the data for the API request to generate hashtags
    $hashtag_data = [
        'model' => MODEL,
        'messages' => [
            ['role' => 'user', 'content' => 'Generate and only reply with ' . $totaltags . ' relevant hashtags based on this status: ' . $status_content]
        ],
        'temperature' => TEMPERATURE,
        'max_tokens' => $hashtagTokens,
    ];

    // Execute API request
    $hashtag_response = executeApiRequest(API_ENDPOINT, $hashtag_data);

    // Check if the API call failed
    if ($hashtag_response === false) {
        exit;
    }

    // Decode the JSON response to extract the hashtags
    $hashtag_response_data = json_decode($hashtag_response, true);
    return $hashtag_response_data['choices'][0]['message']['content'] ?? ''; // Return the generated hashtags or an empty string
}
