<?php

namespace Spatie\DirectoryCleanup\Test;

use Carbon\Carbon;

class DirectoryCleanupTest extends TestCase
{
    /** @test */
    public function it_can_cleanup_the_directories_specified_in_the_config_file()
    {
        $numberOfDirectories = 5;

        $directories = [];

        foreach (range(1, $numberOfDirectories) as $ageInMinutes) {
            $directories[$this->getTempDirectory($ageInMinutes, true)] = ['deleteAllOlderThanMinutes' => $ageInMinutes];
        }

        $this->app['config']->set('laravel-directory-cleanup', compact('directories'));

        foreach ($directories as $directory => $config) {
            foreach (range(1, $numberOfDirectories) as $ageInMinutes) {
                $this->createFile("{$directory}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt", $ageInMinutes);
                $this->createFile("{$directory}".DIRECTORY_SEPARATOR.".{$ageInMinutes}MinutesOld.txt", $ageInMinutes);
            }
        }

        $this->artisan('clean:directories');

        foreach ($directories as $directory => $config) {
            foreach (range(1, $numberOfDirectories) as $ageInMinutes) {
                if ($ageInMinutes < $config['deleteAllOlderThanMinutes']) {
                    $this->assertFileExists("{$directory}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                    $this->assertFileExists("{$directory}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                } else {
                    $this->assertFileDoesNotExist("{$directory}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                    $this->assertFileDoesNotExist("{$directory}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                }
            }
        }
    }

    /** @test */
    public function it_can_cleanup_the_directories_specified_in_the_config_file_recursivly()
    {
        $numberSubOfDirectories = 5;

        $directories = [];

        $this->getTempDirectory('top/'.implode(DIRECTORY_SEPARATOR, range(1, $numberSubOfDirectories)), true);
        $directories[$this->getTempDirectory('top')] = ['deleteAllOlderThanMinutes' => 3];

        $this->app['config']->set('laravel-directory-cleanup', compact('directories'));

        $path = $this->getTempDirectory('top').DIRECTORY_SEPARATOR;
        foreach (range(1, $numberSubOfDirectories + 1) as $level) {
            foreach (range(1, $numberSubOfDirectories) as $ageInMinutes) {
                $this->createFile("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt", $ageInMinutes);
                $this->createFile("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt", $ageInMinutes);
            }
            $path .= "{$level}".DIRECTORY_SEPARATOR;
        }

        $this->artisan('clean:directories');

        foreach ($directories as $directory => $config) {
            $path = $directory.DIRECTORY_SEPARATOR;

            foreach (range(1, $numberSubOfDirectories + 1) as $level) {
                foreach (range(1, $numberSubOfDirectories) as $ageInMinutes) {
                    if ($ageInMinutes < $config['deleteAllOlderThanMinutes']) {
                        $this->assertFileExists("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                        $this->assertFileExists("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                    } else {
                        $this->assertFileDoesNotExist("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                        $this->assertFileDoesNotExist("{$path}".DIRECTORY_SEPARATOR."{$ageInMinutes}MinutesOld.txt");
                    }
                }
                $path .= "{$level}".DIRECTORY_SEPARATOR;
            }
        }
    }

    /** @test */
    public function it_can_cleanup_the_directories_specified_in_the_config_file_but_keep_some_files()
    {
        $directories[$this->getTempDirectory(1, true)] = [
            'deleteAllOlderThanMinutes' => 5,
        ];

        $cleanup_policy = CustomCleanupCleanupPolicy::class;

        $this->app['config']->set('laravel-directory-cleanup', compact('directories', 'cleanup_policy'));

        foreach ($directories as $directory => $config) {
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."keepThisFile.txt", 5);
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."removeThisFile.txt", 5);
        }

        $this->artisan('clean:directories');

        foreach ($directories as $directory => $config) {
            $this->assertFileExists("{$directory}".DIRECTORY_SEPARATOR."keepThisFile.txt");
            $this->assertFileDoesNotExist("{$directory}".DIRECTORY_SEPARATOR."/removeThisFile.txt");
        }
    }

    /** @test */
    public function it_doesnt_fail_if_a_configured_dir_doesnt_exist()
    {
        $directories[$this->getTempDirectory('nodir', false)] = [
            'deleteAllOlderThanMinutes' => 3,
        ];

        $existingDirectory = $this->getTempDirectory(1, true);
        $directories[$existingDirectory] = [
            'deleteAllOlderThanMinutes' => 3,
        ];

        $this->createFile("{$existingDirectory}".DIRECTORY_SEPARATOR."5MinutesOld.txt", 5);

        $this->app['config']->set('laravel-directory-cleanup', compact('directories'));

        $this->artisan('clean:directories');

        $this->assertFileDoesNotExist("{$existingDirectory}".DIRECTORY_SEPARATOR."5MinutesOld.txt");
    }

    /** @test */
    public function it_can_delete_empty_subdirectories()
    {
        $directories[$this->getTempDirectory('deleteEmptySubdirs', true)] = [
            'deleteAllOlderThanMinutes' => 3,
            'deleteEmptySubdirectories' => true,
        ];

        $this->app['config']->set('laravel-directory-cleanup', compact('directories'));

        foreach ($directories as $directory => $config) {
            $this->createDirectory("{$directory}".DIRECTORY_SEPARATOR."emptyDir");
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."emptyDir/5MinutesOld.txt", 5);
            $this->createDirectory("{$directory}".DIRECTORY_SEPARATOR."notEmptyDir");
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."notEmptyDir/1MinutesOld.txt", 1);
            $this->createDirectory("{$directory}".DIRECTORY_SEPARATOR."emptyDirWithHiddenFile");
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."emptyDirWithHiddenFile/.5MinutesOld.txt", 5);
            $this->createDirectory("{$directory}".DIRECTORY_SEPARATOR."notEmptyDirWithHiddenFile");
            $this->createFile("{$directory}".DIRECTORY_SEPARATOR."notEmptyDirWithHiddenFile/.1MinutesOld.txt", 1);
        }

        $this->artisan('clean:directories');

        foreach ($directories as $directory => $config) {
            $this->assertDirectoryExists("{$directory}".DIRECTORY_SEPARATOR."notEmptyDir");
            $this->assertDirectoryDoesNotExist("{$directory}".DIRECTORY_SEPARATOR."emptyDir");
            $this->assertDirectoryExists("{$directory}".DIRECTORY_SEPARATOR."notEmptyDirWithHiddenFile");
            $this->assertDirectoryDoesNotExist("{$directory}".DIRECTORY_SEPARATOR."emptyDirWithHiddenFile");
        }
    }

    protected function createFile(string $fileName, int $ageInMinutes)
    {
        touch($fileName, Carbon::now()->subMinutes($ageInMinutes)->subSeconds(5)->timestamp);
    }

    protected function createDirectory(string $fileName)
    {
        mkdir($fileName);
    }
}
