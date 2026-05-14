<?php

use Illuminate\Support\Facades\Storage;

function getSession(string $sessionKey): ?array
{
    return session($sessionKey);
}

function saveSession(string $sessionName, array $items,array $blocks, array $fields, string $path, int $index = 0): void
{
    session([
        $sessionName => [
            'items' => $items,
            'fields' => $fields,
            'raw' => $blocks,
            'path' => $path,
            'index' => $index,
        ]
    ]);
}

function clearSession(): void
{
    session()->forget('bulk_import');
}

function cleanup(string $storedPath): void
{
    Storage::disk('local')->delete($storedPath);
    clearSession();
}

function DatasetPath(string $filename): string
{
    $path = 'temp-datasets/' . $filename;

    abort_unless(Storage::disk('local')->exists($path), 404, 'File not found.');

    return $path;
}
