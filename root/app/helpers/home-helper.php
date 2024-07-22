<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: home-helper.php
 * Description: ChatGPT API Status Generator
 */

function shareButton($statusText, $imagePath, $accountOwner, $accountName, $statusId)
{
    $filename = basename($imagePath);
    $imageUrl = DOMAIN . "/images/{$accountOwner}/{$accountName}/" . $filename;
    $encodedStatusText = htmlspecialchars($statusText, ENT_QUOTES);

    // SVG code for the combined clipboard and download icon
    $combinedSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M19 3H14.82c-.42-1.16-1.52-2-2.82-2s-2.4.84-2.82 2H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm1 14H8v-2h5v2zm3-4H8v-2h8v2zm0-4H8V7h8v2z" fill="currentColor"/>
    <path d="M12 16l-5.5 5.5 1.41 1.41L11 18.83V16z" fill="currentColor"/></svg>';

    // SVG code for the share icon
    $shareSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M18 16.08c-0.76 0-1.44 0.3-1.96 0.77L8.91 12.7c0.03-0.15 0.04-0.3 0.04-0.46s-0.01-0.31-0.04-0.46l7.13-4.11c0.52 0.48 1.2 0.78 1.96 0.78 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 0.16 0.01 0.31 0.04 0.46l-7.13 4.11c-0.52-0.48-1.2-0.78-1.96-0.78-1.66 0-3 1.34-3 3s1.34 3 3 3c0.76 0 1.44-0.3 1.96-0.77l7.13 4.11c-0.03 0.15-0.04 0.3-0.04 0.46 0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/></svg>';

    // SVG code for the delete icon
    $deleteSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 20l4.5-4.5 4.5 4.5 1.5-1.5-4.5-4.5 4.5-4.5-1.5-1.5-4.5 4.5-4.5-4.5-1.5 1.5 4.5 4.5-4.5 4.5 1.5 1.5z" fill="white"/></svg>';

    // Building the buttons
    $content = "<div id='share-buttons-{$statusId}' class='share-buttons'>";
    $content .= "<div class='left-buttons'>";
    $content .= "<button class='blue-button combined-button' data-text='{$encodedStatusText}' data-url='{$imageUrl}' data-filename='{$filename}' title='Copy Text and Download Image'>{$combinedSvg}</button>";
    $content .= "<button class='green-button share-button' data-text='{$encodedStatusText}' data-url='{$imageUrl}' title='Share'>{$shareSvg}</button>";
    $content .= "</div>";

    $content .= "<form action='/home' method='POST' class='delete-button-form'>";
    $content .= "<input type='hidden' name='account' value='" . htmlspecialchars($accountName) . "'>";
    $content .= "<input type='hidden' name='username' value='" . htmlspecialchars($accountOwner) . "'>";
    $content .= "<input type='hidden' name='id' value='{$statusId}'>";
    $content .= "<input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}'>";
    $content .= "<button class='red-button' type='submit' name='delete_status'>{$deleteSvg}</button>";
    $content .= "</form>";

    $content .= "</div>";

    return $content;
}
