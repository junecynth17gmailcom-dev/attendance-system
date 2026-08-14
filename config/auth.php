<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(?string $role = null): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {

        header('Location: ../index.php');
        exit;
    }

    if ($role !== null && $_SESSION['role'] !== $role) {

        http_response_code(403);
        exit('Access denied.');
    }
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}