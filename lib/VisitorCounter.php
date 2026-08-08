<?php

class VisitorCounter
{
    private const STORAGE_DIR = __DIR__ . "/../data";
    private const STORAGE_FILE = "visitor_count.txt";

    public static function increment(): int
    {
        $path = self::storagePath();

        if (!self::ensureStorageDirectory()) {
            return self::readCountFromFile($path);
        }

        $handle = @fopen($path, "c+");
        if ($handle === false) {
            return self::readCountFromFile($path);
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return self::readCountFromFile($path);
        }

        $count = self::readCountFromHandle($handle);
        $count++;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, (string) $count);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $count;
    }

    public static function getCount(): int
    {
        $path = self::storagePath();
        if (!file_exists($path)) {
            return 0;
        }

        $handle = @fopen($path, "r");
        if ($handle === false) {
            return 0;
        }

        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            return 0;
        }

        $count = self::readCountFromHandle($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $count;
    }

    private static function ensureStorageDirectory(): bool
    {
        $directory = self::STORAGE_DIR;

        if (is_dir($directory)) {
            return true;
        }

        return @mkdir($directory, 0755, true) || is_dir($directory);
    }

    private static function storagePath(): string
    {
        return self::STORAGE_DIR . "/" . self::STORAGE_FILE;
    }

    private static function readCountFromHandle($handle): int
    {
        rewind($handle);
        $contents = stream_get_contents($handle);
        $count = (int) trim((string) $contents);

        return $count >= 0 ? $count : 0;
    }

    private static function readCountFromFile(string $path): int
    {
        if (!is_readable($path)) {
            return 0;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return 0;
        }

        $count = (int) trim($contents);

        return $count >= 0 ? $count : 0;
    }
}
