<?php

namespace App\Services;

class DataLoader
{
    public static function load(string $filename): array
    {
        $path = storage_path("app/data/{$filename}");
        if (!file_exists($path)) {
            throw new \Exception("Data file not found: {$filename}");
        }

        return json_decode(file_get_contents($path), true);
    }
}
