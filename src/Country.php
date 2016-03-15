<?php namespace Znck\Countries;

/**
 * Class State.
 *
 * @property string $name
 * @property string $code
 */
trait Country
{
    /**
     * @var string
     */
    protected static $locale = 'en';

    /**
     * @var Translator
     */
    protected static $countries;

    /**
     * Boot city.
     */
    public static function bootCity()
    {
        static::$locale = config('app.locale', 'en');
        static::$countries = app('translator.countries');
    }

    /**
     * @param string $val
     *
     * @return string
     */
    public function getNameAttribute(string $val)
    {
        if (static::$locale === 'en') {
            return $val;
        }

        $name = static::$countries->getName($this->code, static::$locale);

        if ($name === $this->code) {
            return $val;
        }

        return $name;
    }
}
