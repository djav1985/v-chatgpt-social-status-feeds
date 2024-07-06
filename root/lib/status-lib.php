<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: status-helper.php
 * Description: ChatGPT API Status Generator
 */

function generateStatus($accountName, $accountOwner)
{
    $system_message = SYSTEM_MSG;

    // Retrieve account information from the database
    $accountInfo = getAcctInfo($accountOwner, $accountName);

    // Get prompt, link, hashtags, image prompts, and platform from account info
    $prompt = $accountInfo->prompt;
    $link = $accountInfo->link;
    $hashtags = $accountInfo->hashtags;
    $image_prompt = $accountInfo->image_prompt;
    $platform = $accountInfo->platform;  // Retrieve platform type

    // Retrieve the status content from API
    $status_content = getStatus($link, $prompt, $platform, $system_message);

    // Generate image associated with the status using image prompts
    $image_name = getImage($accountName, $accountOwner, $image_prompt);

    // Conditionally append hashtags if requested
    if ($hashtags) {
        $hashtag_content = getHashtags($status_content, $platform); // Pass platform to adjust number of hashtags
        $status_content .= ' ' . $hashtag_content;
    }

    // Save status with the generated image name
    saveStatus($accountName, $accountOwner, $status_content, $image_name);
}

function getStatus($link, $prompt, $platform, $system_message)
{
    // Determine the token count for status updates based on the platform
    if ($platform === 'facebook') {
        $statusTokens = 256;
    } elseif ($platform === 'twitter') {
        $statusTokens = 64;
    } elseif ($platform === 'instagram') {
        $statusTokens = 128;
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

    $status_data = [
        'model' => MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => 'Generate a social status under 256 characters with NO hashtags: ' . $prompt . ' Also add the following CTA: Visit: ' . $link]
        ],
        'temperature' => TEMPERATURE,
        'max_tokens' => $statusTokens,
    ];

    $ch = curl_init(API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($status_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $status_response = curl_exec($ch);
    curl_close($ch);

    if ($status_response === false) {
        return 'API request failed'; // Consider how to handle failures more robustly
    }

    $status_response_data = json_decode($status_response, true);
    return $status_response_data['choices'][0]['message']['content'] ?? '';
}

function getHashtags($status_content, $platform)
{
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

    // Determine the number of hashtags and tokens based on the platform
    if ($platform === 'facebook') {
        $totaltags = '3 to 5';
        $hashtagTokens = 60;
    } elseif (
        $platform === 'twitter'
    ) {
        $totaltags = '3';
        $hashtagTokens = 30;
    } elseif ($platform === 'instagram') {
        $totaltags = '20 to 30';
        $hashtagTokens = 128;
    }

    $hashtag_data = [
        'model' => MODEL,
        'messages' => [
            ['role' => 'user', 'content' => 'Generate and only reply with ' . $totaltags . ' relevant hashtags based on this status: ' . $status_content]
        ],
        'temperature' => TEMPERATURE,
        'max_tokens' => $hashtagTokens,
    ];

    $ch = curl_init(API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($hashtag_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $hashtag_response = curl_exec($ch);
    curl_close($ch);

    if ($hashtag_response === false) {
        return ''; // Return an empty string if the API request fails
    }

    $hashtag_response_data = json_decode($hashtag_response, true);
    return $hashtag_response_data['choices'][0]['message']['content'] ?? '';
}

function getImage($accountName, $accountOwner, $image_prompt)
{
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

    $image_data = [
        'model' => 'dall-e-3',
        'prompt' => $image_prompt,
        'n' => 1,
        'quality' => "standard",
        'size' => "1792x1024"
    ];

    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($image_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $image_response = curl_exec($ch);
    curl_close($ch);

    $image_response_data = json_decode($image_response, true);
    // Adjust the way the image URL is retrieved
    $image_url = $image_response_data['data'][0]['url'] ?? '';

    if (!empty($image_url)) {
        // Generate a random name for the image
        $random_name = uniqid() . '.png';
        // Path to save the image
        $image_path = __DIR__ . '/../public/images/' . $accountOwner . '/' . $accountName . '/' . $random_name;
        // Save the image
        file_put_contents($image_path, file_get_contents($image_url));

        // Return the image name
        return $random_name;
    }
    return ''; // Return an empty string if no URL is fetched
}


function saveStatus($accountName, $accountOwner, $status_content, $image_name)
{
    $db = new Database();
    $sql = "INSERT INTO status_updates (username, account, status, created_at, status_image) VALUES (:username, :account, :status, NOW(), :status_image)";
    $db->query($sql);
    $db->bind(':username', $accountOwner);
    $db->bind(':account', $accountName);
    $db->bind(':status', $status_content);
    $db->bind(':status_image', $image_name);
    $db->execute();
}
