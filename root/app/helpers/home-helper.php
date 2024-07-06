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

    // SVG code for the clipboard icon
    $clipboardSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 3H14.82c-.42-1.16-1.52-2-2.82-2s-2.4.84-2.82 2H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm1 14H8v-2h5v2zm3-4H8v-2h8v2zm0-4H8V7h8v2z" fill="currentColor"/></svg>';

    // SVG code for the download icon
    $downloadSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 20h14v-2H5v2zm7-16l-5.5 5.5 1.41 1.41L11 7.83V16h2V7.83l3.09 3.08 1.41-1.41L12 4z" fill="currentColor"/></svg>';

    // SVG code for the delete icon
    $deleteSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 20l4.5-4.5 4.5 4.5 1.5-1.5-4.5-4.5 4.5-4.5-1.5-1.5-4.5 4.5-4.5-4.5-1.5 1.5 4.5 4.5-4.5 4.5 1.5 1.5z" fill="white"/></svg>';

    // Building the buttons
    $content = "<div id='share-buttons-{$statusId}' class='share-buttons'>";
    $content .= "<button class='blue-button' data-text='{$encodedStatusText}' title='Copy Text'>{$clipboardSvg}</button>";
    $content .= "<button class='blue-button' data-url='{$imageUrl}' data-filename='{$filename}' title='Download Image'>{$downloadSvg}</button>";

    // Add the delete button/form
    $content .= "<form action='/home' method='POST'>";
    $content .= "<input type='hidden' name='account' value='" . htmlspecialchars($accountName) . "'>";
    $content .= "<input type='hidden' name='username' value='" . htmlspecialchars($accountOwner) . "'>";
    $content .= "<input type='hidden' name='id' value='{$statusId}'>";
    $content .= "<input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}'>";
    $content .= "<button class='red-button' type='submit' name='delete_status'>{$deleteSvg}</button>";
    $content .= "</form>";

    $content .= "</div>";

    return $content;
}
