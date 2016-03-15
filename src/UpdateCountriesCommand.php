<?php namespace Znck\Countries;

use DB;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class UpdateCountriesCommand extends Command
{
    const QUERY_LIMIT = 100;
    const INSTALL_HISTORY = 'vendor/znck/countries/install.txt';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'countries:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update/Install countries in database.';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var FileLoader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $countries;

    /**
     * @var string
     */
    protected $hash;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Application $app
     */
    public function __construct(Filesystem $files, Application $app)
    {
        parent::__construct();

        $this->files = $files;

        $this->path = dirname(__DIR__).'/data/en';

        $this->loader = new FileLoader($files, dirname(__DIR__).'/data');

        $config = $app->make('config');
        $this->countries = $config->get('countries');

        if ($this->files->exists(storage_path(self::INSTALL_HISTORY))) {
            $this->hash = $this->files->get(storage_path(self::INSTALL_HISTORY));
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $countries = $this->files->files($this->path);

        $countries = [];

        $data = $this->loader->load('en');
        foreach ($data as $key => $name) {
            $countries[] = [
                'name' => $name,
                'code' => "${key}",
            ];
        }

        $countries = Collection::make($countries);
        $hash = md5($countries->toJson());

        if ($hash === $this->hash) {
            return false;
        }

        $countryCodes = $countries->pluck('code');

        $countryCodes = $countries->pluck('code')->unique();

        $existingCountryIDs = Collection::make(
            DB::table($this->countries)->whereIn('code', $countryCodes)->pluck('id', 'code')
        );
        $countries->map(
            function ($item) use ($existingCountryIDs) {
                if ($existingCountryIDs->has($item['code'])) {
                    $item['id'] = $existingCountryIDs->get($item['code']);
                }

                return $item;
            }
        );

        $countries = $countries->groupBy(
            function ($item) {
                return array_has($item, 'id') ? 'update' : 'create';
            }
        );

        DB::transaction(
            function () use ($countries, $hash) {
                $create = Collection::make($countries->get('create'));
                $update = Collection::make($countries->get('update'));

                foreach ($create->chunk(static::QUERY_LIMIT) as $entries) {
                    DB::table($this->countries)->insert($entries);
                }

                foreach ($update->chunk(static::QUERY_LIMIT) as $entries) {
                    DB::table($this->countries)->update($entries);
                }
                $this->files->put(storage_path(static::INSTALL_HISTORY), $hash);
            }
        );
    }
}