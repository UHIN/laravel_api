<?php

namespace uhin\laravel_api\Console\Commands;

trait BaseCommand
{

    /**
     * Copies the stub file to the given destination
     *
     * @param $stub
     * @param $destination
     */
    protected function copyStub($stub, $destination)
    {
        $this->mkdir(dirname($destination));
        $this->put($stub, $destination);
    }

    /**
     * Creates a directory if it doesn't already exist
     *
     * @param $directory
     * @return bool
     */
    protected function mkdir($directory)
    {
        if (!is_dir($directory)) {
            return mkdir($directory, 0777, true);
        } else {
            return true;
        }
    }

    /**
     * Copies a file from one location to another
     *
     * @param $from
     * @param $to
     * @return bool|int
     */
    protected function put($from, $to)
    {
        if (!file_exists($to)) {
            return file_put_contents($to, file_get_contents($from));
        } else {
            return true;
        }
    }

    /**
     * Deletes a given file
     *
     * @param $file
     * @return bool
     */
    protected function deleteFile($file) {
        if (file_exists($file)) {
            return unlink($file);
        } else {
            return true;
        }
    }

    /**
     * Deletes all files in a given directory
     *
     * @param $directory
     * @return bool
     */
    protected function deleteFiles($directory) {
        if (!is_dir($directory)) {
            return true;
        }
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$directory/$file")) ? $this->deleteDirectory("$directory/$file") : unlink("$directory/$file");
        }
    }

    /**
     * Deletes all files in a given directory and then removes the directory
     *
     * @param $directory
     * @return bool
     */
    protected function deleteDirectory($directory) {
        if (!is_dir($directory)) {
            return true;
        }
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$directory/$file")) ? $this->deleteDirectory("$directory/$file") : unlink("$directory/$file");
        }
        return rmdir($directory);
    }

}
