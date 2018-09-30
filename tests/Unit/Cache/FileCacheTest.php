<?php

/*
 * PHP script for downloading videos from youtube
 * Copyright (C) 2012-2018  John Eckman
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, see <http://www.gnu.org/licenses/>.
 */

namespace YoutubeDownloader\Tests\Unit\Cache;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\SimpleCache\CacheInterface;
use YoutubeDownloader\Cache\Cache;
use YoutubeDownloader\Cache\CacheException;
use YoutubeDownloader\Cache\FileCache;
use YoutubeDownloader\Tests\Fixture\Cache\DataProviderTrait;
use YoutubeDownloader\Tests\Fixture\Cache\Psr16CacheAdapter;
use YoutubeDownloader\Tests\Fixture\TestCase;

class FileCacheTest extends TestCase
{
    use DataProviderTrait;

    /**
     * @test createFromDirectory()
     */
    public function createFromDirectory()
    {
        $root = vfsStream::setup('cache');

        $this->assertInstanceOf(
            FileCache::class,
            FileCache::createFromDirectory($root->url())
        );
    }

    /**
     * @test FileCache is is compatible with Psr\SimpleCache\CacheInterface
     */
    public function isPsr16Compatible()
    {
        $root = vfsStream::setup('cache');

        $cache = FileCache::createFromDirectory($root->url());

        $adapter = new Psr16CacheAdapter($cache);

        $this->assertInstanceOf(CacheInterface::class, $adapter);
        $this->assertInstanceOf(Cache::class, $adapter);
    }

    /**
     * @test createFromDirectory()
     */
    public function createFromDirectoryThrowsExceptionIfFolderNotExists()
    {
        $root = vfsStream::setup('cache');

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('cache directory "vfs://not_existing" does not exist.');

        FileCache::createFromDirectory('vfs://not_existing');
    }

    /**
     * @test createFromDirectory()
     */
    public function createFromDirectoryThrowsExceptionIfFolderIsNotDirectory()
    {
        $root = vfsStream::setup('cache');
        vfsStream::newFile('file', 0000)->at($root);

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('cache directory "vfs://cache/file" is not a directory.');

        FileCache::createFromDirectory('vfs://cache/file');
    }

    /**
     * @test createFromDirectory()
     */
    public function createFromDirectoryThrowsExceptionIfFolderNotReadable()
    {
        $root = vfsStream::setup('cache', 0000);

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('cache directory "vfs://cache" is not readable.');

        FileCache::createFromDirectory($root->url());
    }

    /**
     * @test createFromDirectory()
     */
    public function createFromDirectoryThrowsExceptionIfFolderNotWritable()
    {
        $root = vfsStream::setup('cache', 0400);

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('cache directory "vfs://cache" is not writable.');

        FileCache::createFromDirectory($root->url());
    }

    /**
     * @test get()
     */
    public function getReturnsValue()
    {
        $root = vfsStream::setup('cache', 0600);
        vfsStream::newFile('key', 0600)
            ->withContent(serialize([
                'foobar',
                null,
            ]))
            ->at($root);

        $cache = FileCache::createFromDirectory($root->url());

        $this->assertSame('foobar', $cache->get('key'));
    }

    /**
     * @test get()
     *
     * @dataProvider InvalidKeyProvider
     *
     * @param mixed $invalid_key
     * @param mixed $exception_name
     * @param mixed $message
     */
    public function getWithInvalidKeyThrowsException($invalid_key, $exception_name, $message)
    {
        $root = vfsStream::setup('cache');

        $cache = FileCache::createFromDirectory($root->url());

        $this->expectException($exception_name);
        $this->expectExceptionMessage($message);

        $cache->get($invalid_key);
    }

    /**
     * @test get()
     */
    public function getNotExistingReturnsDefault()
    {
        $root = vfsStream::setup('cache', 0600);

        $cache = FileCache::createFromDirectory($root->url());

        $this->assertSame('default', $cache->get('key', 'default'));
    }

    /**
     * @test get()
     */
    public function getNotUnserializableReturnsDefault()
    {
        $root = vfsStream::setup('cache', 0600);
        vfsStream::newFile('key', 0600)
            ->withContent('foobar')
            ->at($root);

        $cache = FileCache::createFromDirectory($root->url());

        $this->assertSame('default', $cache->get('key', 'default'));
    }

    /**
     * @test get()
     */
    public function getExpiredReturnsDefault()
    {
        $root = vfsStream::setup('cache', 0600);
        vfsStream::newFile('key', 0600)
            ->withContent(serialize([
                'foobar',
                1,
            ]))
            ->at($root);

        $cache = FileCache::createFromDirectory($root->url());

        $this->assertSame('default', $cache->get('key', 'default'));

        // The expired cache should be deleted
        $this->assertFalse($root->hasChildren());
    }

    /**
     * @test set()
     */
    public function setReturnsTrue()
    {
        $root = vfsStream::setup('cache', 0600);

        $cache = FileCache::createFromDirectory(
            $root->url(),
            ['writeFlags' => 0]
        );

        $this->assertTrue($cache->set('key', 'foobar'));

        $this->assertTrue($root->hasChild('key'));
        $this->assertSame(
            'a:2:{i:0;s:6:"foobar";i:1;N;}',
            $root->getChild('key')->getContent()
        );
    }

    /**
     * @test set()
     */
    public function setWithTtlReturnsTrue()
    {
        $root = vfsStream::setup('cache', 0600);

        $cache = FileCache::createFromDirectory(
            $root->url(),
            ['writeFlags' => 0]
        );

        $this->assertTrue($cache->set('key', 'foobar', 3600));

        $this->assertTrue($root->hasChild('key'));
        $this->assertSame(
            sprintf('a:2:{i:0;s:6:"foobar";i:1;i:%s;}', time()+3600),
            $root->getChild('key')->getContent()
        );
    }

    /**
     * @test set()
     *
     * @dataProvider InvalidKeyProvider
     *
     * @param mixed $invalid_key
     * @param mixed $exception_name
     * @param mixed $message
     */
    public function setWithInvalidKeyThrowsException($invalid_key, $exception_name, $message)
    {
        $root = vfsStream::setup('cache');

        $cache = FileCache::createFromDirectory($root->url());

        $this->expectException($exception_name);
        $this->expectExceptionMessage($message);

        $cache->set($invalid_key, 'value');
    }

    /**
     * @test delete()
     */
    public function deleteReturnsTrue()
    {
        $root = vfsStream::setup('cache', 0600);
        vfsStream::newFile('key', 0600)
            ->withContent(serialize([
                'foobar',
                null,
            ]))
            ->at($root);

        $cache = FileCache::createFromDirectory($root->url());

        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($root->hasChildren());
    }

    /**
     * @test delete()
     *
     * @dataProvider InvalidKeyProvider
     *
     * @param mixed $invalid_key
     * @param mixed $exception_name
     * @param mixed $message
     */
    public function deleteWithInvalidKeyThrowsException($invalid_key, $exception_name, $message)
    {
        $root = vfsStream::setup('cache');

        $cache = FileCache::createFromDirectory($root->url());

        $this->expectException($exception_name);
        $this->expectExceptionMessage($message);

        $cache->delete($invalid_key);
    }
}
