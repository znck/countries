<?php namespace Znck\Countries;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class FileLoader
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $path;

    /**
     * FileLoader constructor.
     *
     * @param Filesystem $files
     * @param string $path
     */
    public function __construct(Filesystem $files, string $path)
    {
        $this->files = $files;
        $this->path = $path;
    }

    /**
     * Loads a locale.
     *
     * @param string $locale A locale
     *
     * @throws NotFoundResourceException when the resource cannot be found
     * @throws InvalidResourceException  when the resource cannot be loaded
     *
     * @return array
     */
    public function load(string $locale):array
    {
        $filename = "{$this->path}/{$locale}.php";
        try {
            $loaded = $this->files->getRequire($filename);
            if (!is_array($loaded)) {
                throw new InvalidResourceException();
            }

            return $loaded;
        } catch (FileNotFoundException $e) {
            throw new NotFoundResourceException("$filename not found.", 0, $e);
        } catch (\Throwable $e) {
            throw new InvalidResourceException("$filename has invalid resources.", 0, $e);
        }
    }
}
