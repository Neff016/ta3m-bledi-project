<?php
class Store
{
    private const DIR = __DIR__ . '/../storage';

    // Tables that "reset" must never wipe
    private const KEEP = ['utilisateurs'];

    private static function path(string $key): string
    {
        return self::DIR . '/' . $key . '.json';
    }

    public static function all(string $key): array
    {
        if (!is_file(self::path($key))) {
            self::replace($key, require __DIR__ . '/seed/' . $key . '.php');
        }
        $rows = json_decode((string)file_get_contents(self::path($key)), true);
        return is_array($rows) ? $rows : [];
    }

    public static function replace(string $key, array $rows): void
    {
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }
        $tmp = tempnam(self::DIR, 'tmp');
        file_put_contents(
            $tmp,
            json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        rename($tmp, self::path($key));
    }

    public static function nextId(string $key, string $idField): int
    {
        $ids = array_column(self::all($key), $idField);
        return $ids ? max($ids) + 1 : 1;
    }

    // Back to the seed data (villes + projections only, users are kept)
    public static function reset(): void
    {
        foreach (glob(self::DIR . '/*.json') ?: [] as $file) {
            if (!in_array(basename($file, '.json'), self::KEEP, true)) {
                unlink($file);
            }
        }
    }
}
