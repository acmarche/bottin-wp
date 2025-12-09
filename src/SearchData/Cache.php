<?php

namespace AcMarche\Bottin\SearchData;

use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;

class Cache
{
    public static $instanceObject = null;
    private static ?SluggerInterface $slugger = null;

    public static function instance(): CacheInterface
    {
        if (self::$instanceObject) {
            return self::$instanceObject;
        }

        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            self::$instanceObject =
                new ApcuAdapter(
                // a string prefixed to the keys of the items stored in this cache
                    $namespace = 'newmarche',

                    // the default lifetime (in seconds) for cache items that do not define their
                    // own lifetime, with a value 0 causing items to be stored indefinitely (i.e.
                    // until the APCu memory is cleared)
                    $defaultLifetime = 3600,

                    // when set, all keys prefixed by $namespace can be invalidated by changing
                    // this $version string
                    $version = null
                );
        } else {
            self::$instanceObject =
                new FilesystemAdapter(
                // a string used as the subdirectory of the root cache directory, where cache
                // items will be stored
                    $namespace = 'newmarche2',

                    // the default lifetime (in seconds) for cache items that do not define their
                    // own lifetime, with a value 0 causing items to be stored indefinitely (i.e.
                    // until the files are deleted)
                    $defaultLifetime = 3600,

                    // the main cache directory (the application needs read-write permissions on it)
                    // if none is specified, a directory is created inside the system temporary directory
                    $directory = null
                );
        }

        //return new TagAwareAdapter(self::$instanceObject, self::$instanceObject);
        return self::$instanceObject;
    }

    public static function refresh(string $code): void
    {
        $request = Request::createFromGlobals();
        $refresh = $request->get('refresh', null);

        $cache = self::instance();
        if ($refresh) {
            $cache->delete($code);
        }
    }

    public static function generateCodeBottin(int $blogId, string $slug): string
    {
        return 'bottin-fiche-'.$blogId.'-'.$slug;
    }

    public static function generateCodeArticle(int $blogId, int $postId): string
    {
        return 'post-'.$blogId.'-'.$postId;
    }

    public static function generateCodeCategory(int $blogId, int $categoryId): string
    {
        return 'category-'.$blogId.'-'.$categoryId;
    }
    public static function generateKey(string $cacheKey): string
    {
        if (!self::$slugger) {
            self::$slugger = new AsciiSlugger();
        }

        $keyUnicode = new UnicodeString($cacheKey);

        return self::$slugger->slug($keyUnicode->ascii()->toString());
    }
    // Helper method to delete an item from cache
    public static function delete(string $key): bool
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->delete($cacheKey);
    }

    // Helper method to get an item from cache
    public static function get(string $key, callable $callback, ?float $beta = null, ?array $tags = null)
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->get($cacheKey, $callback, $beta, $tags);
    }
}
