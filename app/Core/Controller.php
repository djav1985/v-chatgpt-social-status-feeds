<?php
namespace App\Core;

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../Views/layouts/header.php';
        require __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layouts/footer.php';
    }
}
