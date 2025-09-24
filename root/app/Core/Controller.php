<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Controller.php
 * Description: AI Social Status Generator
 */

namespace App\Core;

class Controller
{
    /**
     * Renders a view file with the provided data.
     *
     * @param string $view The view name relative to the Views directory.
     * @param array $data Optional data extracted for use within the view.
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}
