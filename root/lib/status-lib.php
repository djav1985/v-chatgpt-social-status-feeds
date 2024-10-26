<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: status-helper.php
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
    $status_content = getStatus($link, $prompt, $platform, $system_message);

    // Generate the image prompt based on the status content and initial image prompt
    $generated_image_prompt = getImagePrompt($image_prompt, $status_content);

    // Generate the actual image associated with the status using the generated image prompt
    $image_name = getImage($accountName, $accountOwner, $generated_image_prompt);

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


function logApiInteraction($data, $response) {
    // Create a log entry by encoding the request data and response to JSON format
    $logData = "Request: " . json_encode($data) . "\nResponse: " . json_encode($response) . "\n\n";

    // Append the log data to the api.log file in the parent directory
    file_put_contents(__DIR__ . '/../api.log', $logData, FILE_APPEND);
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

    // Set up headers for the API request including content type and authorization
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

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

    // Initialize cURL session for the API request
    $ch = curl_init(API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true); // Set the request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($status_data)); // Set the JSON encoded request data
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the request headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Ensure the response is returned as a string
    $status_response = curl_exec($ch); // Execute the API request and fetch response
    curl_close($ch); // Close cURL session

    // Log the API interaction for debugging or monitoring purposes
    logApiInteraction($status_data, $status_response);

    // Check if the API call was unsuccessful
    if ($status_response === false) {
        return 'API request failed'; // Return an error message if the request failed
    }

    // Decode the JSON response to extract the generated status update
    $status_response_data = json_decode($status_response, true);
    return $status_response_data['choices'][0]['message']['content'] ?? ''; // Return the generated content or an empty string
}

function getImagePrompt($image_prompt, $status_content)
{
    // Set up headers for the image generation prompt API request
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

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

    // Initialize cURL session for the image prompt generation API request
    $ch = curl_init(API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true); // Set the request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt_data)); // Set the JSON encoded request data
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the request headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Ensure the response is returned as a string
    $prompt_response = curl_exec($ch); // Execute the API request and fetch response
    curl_close($ch); // Close cURL session

    // Log the API interaction for debugging or monitoring purposes
    logApiInteraction($prompt_data, $prompt_response);

    // Check if the API call was unsuccessful
    if ($prompt_response === false) {
        return 'API request failed'; // Return an error message if the request failed
    }

    // Decode the JSON response to extract the generated image prompt
    $prompt_response_data = json_decode($prompt_response, true);
    return $prompt_response_data['choices'][0]['message']['content'] ?? ''; // Return the generated prompt or an empty string
}


function getHashtags($status_content, $platform)
{
    // Set the headers for the API request, including content type and authorization
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

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

    // Initialize cURL session for API request
    $ch = curl_init(API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true); // Set request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($hashtag_data)); // Set the JSON encoded data
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the request headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Ensure the response is returned as a string
    $hashtag_response = curl_exec($ch); // Execute the request and fetch response
    curl_close($ch); // Close cURL session

    // Log the interaction for debugging or record keeping
    logApiInteraction($hashtag_data, $hashtag_response);

    // Check if the API call failed
    if ($hashtag_response === false) {
        return ''; // Return an empty string if there was no response
    }

    // Decode the JSON response to extract the hashtags
    $hashtag_response_data = json_decode($hashtag_response, true);
    return $hashtag_response_data['choices'][0]['message']['content'] ?? ''; // Return the generated hashtags or an empty string
}

function getImage($accountName, $accountOwner, $generated_image_prompt)
{
    // Set the headers for the image generation API request
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

    // Prepare the data for the API to generate the image
    $image_data = [
        'model' => 'dall-e-3',          // Specify the model to use for image generation
        'prompt' => $generated_image_prompt, // The prompt for generating the image
        'n' => 1,                         // Number of images to generate
        'quality' => "standard",         // Quality setting for the image
        'size' => "1792x1024"            // Dimensions of the generated image
    ];

    // Initialize cURL session for the image generation API request
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt($ch, CURLOPT_POST, true); // Set request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($image_data)); // Set the JSON encoded data
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the request headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Ensure the response is returned as a string
    $image_response = curl_exec($ch); // Execute the request and fetch response
    curl_close($ch); // Close cURL session

    // Log the interaction for debugging or record keeping
    logApiInteraction($image_data, $image_response);

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


function saveStatus($accountName, $accountOwner, $status_content, $image_name)
{
    // Create a new instance of the Database class
    $db = new Database();

    // SQL query to insert a new status update into the database
    $sql = "INSERT INTO status_updates (username, account, status, created_at, status_image) VALUES (:username, :account, :status, NOW(), :status_image)";

    // Prepare the SQL statement for execution
    $db->query($sql);

    // Bind the parameters to the SQL query
    $db->bind(':username', $accountOwner); // Bind the account owner's username
    $db->bind(':account', $accountName);   // Bind the account name
    $db->bind(':status', $status_content); // Bind the status content
    $db->bind(':status_image', $image_name); // Bind the image file name

    // Execute the SQL statement to insert the data into the database
    $db->execute();
}
