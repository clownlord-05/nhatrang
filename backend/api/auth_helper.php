<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function current_user_role(): ?string
{
    $admin = session_admin();
    return $admin['role'] ?? null;
}

function json_fail(int $httpCode, string $message): void
{
    json_response(['success' => false, 'message' => $message], $httpCode);
}

function require_login(): array
{
    return require_auth();
}

function require_role(array $allowedRoles): array
{
    $roles = array_map(static fn (string $role): string => $role === 'admin' ? 'editor' : $role, $allowedRoles);
    return require_auth($roles);
}
