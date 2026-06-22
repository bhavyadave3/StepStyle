<?php

function sanitize(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function formatPrice(float $amount): string
{
    return "₹" . number_format($amount, 2);
}

function generateOrderNumber(): string
{
    return 'ORD-' . strtoupper(uniqid());
}

function getCurrentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}