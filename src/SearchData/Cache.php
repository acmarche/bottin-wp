<?php

namespace AcMarche\Bottin\SearchData;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;

class Cache
{
    private static ?SluggerInterface $slugger = null;
    private static ?CacheInterface $cache = null;

    public static function instance(): CacheInterface|RedisTagAwareAdapter
    {
        if (!self::$cache) {
            $client = RedisAdapter::createConnection('redis://localhost');
            self::$cache = new RedisTagAwareAdapter($client, 'marchebe', 60 * 60 * 26);
        }

        return self::$cache;
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
        return 'bottin-fiche-' . $blogId . '-' . $slug;
    }

    public static function generateCodeArticle(int $blogId, int $postId): string
    {
        return 'post-' . $blogId . '-' . $postId;
    }

    public static function generateCodeCategory(int $blogId, int $categoryId): string
    {
        return 'category-' . $blogId . '-' . $categoryId;
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

    /**
     *
     * @throws InvalidArgumentException
     */
    public static function get(string $key, callable $callback, ?float $beta = null, ?array $tags = null)
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->get($cacheKey, $callback, $beta, $tags);
    }

    // Helper method to get an item from cache only if it exists (no computation)
    public static function getIfExists(string $cacheKey): mixed
    {
        $cache = self::instance();

        // Both ApcuAdapter and FilesystemAdapter implement CacheItemPoolInterface
        if ($cache instanceof CacheItemPoolInterface) {
            try {
                $item = $cache->getItem($cacheKey);
            } catch (InvalidArgumentException $e) {
                return null;
            }
            return $item->isHit() ? $item->get() : null;
        }

        return null;
    }
}
