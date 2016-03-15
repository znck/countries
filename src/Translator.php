<?php namespace Znck\Countries;

use Illuminate\Support\Str;

class Translator
{
    /**
     * @var FileLoader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $fallbackLocale;

    /**
     * @var array
     */
    protected $loaded = [];

    /**
     * Translator constructor.
     *
     * @param FileLoader $loader
     * @param string $locale
     */
    public function __construct(FileLoader $loader, string $locale)
    {
        $this->loader = $loader;
        $this->fallbackLocale = $locale;
    }

    /**
     * @param string $locale
     *
     * @return bool
     */
    protected function isLoaded(string $locale)
    {
        return isset($this->loaded[$locale]);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    protected function parseKey(string $key)
    {
        return preg_split('/[ .]/', Str::upper($key));
    }

    /**
     * @param string $key
     * @param string|null $locale
     *
     * @return string
     */
    public function get(string $key, string $locale = null)
    {
        list($country) = $this->parseKey($key);

        $locale = $locale ?? $this->fallbackLocale;

        $this->load($locale);

        if ($this->has($locale, $country)) {
            return $this->loaded[$locale][$country];
        }

        return $key;
    }

    /**
     * @param string $key
     * @param string|null $locale
     *
     * @return string
     */
    public function getName(string $key, string $locale = null)
    {
        return $this->get($key, $locale);
    }

    /**
     * @param string $locale
     */
    protected function load(string $locale)
    {
        if ($this->isLoaded($locale)) {
            return;
        }

        $this->loaded[$locale] = $this->loader->load($locale);
    }

    /**
     * @param string $locale
     * @param string $country
     *
     * @return bool
     */
    protected function has(string $locale, string $country)
    {
        return isset($this->loaded[$locale][$country]);
    }
}
