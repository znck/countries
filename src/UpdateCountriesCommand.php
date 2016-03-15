<?php namespace Znck\Countries;

use DB;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountriesCommand extends Command
{
    const QUERY_LIMIT = 100;
    const INSTALL_HISTORY = 'vendor/znck/countries/install.txt';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'countries:update {--f|force : Force update}';

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
        $this->countries = $config->get('countries.countries');

        if (!$this->files->isDirectory(dirname(storage_path(self::INSTALL_HISTORY)))) {
            $this->files->makeDirectory(dirname(storage_path(self::INSTALL_HISTORY)), 0755, true);
        }

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

        if (!$this->option('force') && $hash === $this->hash) {
            $this->line("No new country.");
            return false;
        }

        $countryCodes = $countries->pluck('code')->unique();

        $existingCountryIDs = Collection::make(
            DB::table($this->countries)->whereIn('code', $countryCodes)->pluck('id', 'code')
        );

        $countries = $countries->map(function ($item) use ($existingCountryIDs) {
            if ($existingCountryIDs->has($item['code'])) {
                $item['id'] = $existingCountryIDs->get($item['code']);
            }

            return $item;
        });

        $countries = $countries->groupBy(function ($item) {
            return array_has($item, 'id') ? 'update' : 'create';
        });

        DB::transaction(function () use ($countries, $hash) {
            $create = $countries->get('create', Collection::make());
            $update = $countries->get('update', Collection::make());

            foreach ($create->chunk(static::QUERY_LIMIT) as $entries) {
                DB::table($this->countries)->insert($entries->toArray());
            }

            foreach ($update as $entries) {
                DB::table($this->countries)->where('id', $entries['id'])->update($entries);
            }
            $this->line("{$create->count()} countries created. {$update->count()} countries updated.");
            $this->files->put(storage_path(static::INSTALL_HISTORY), $hash);
        });

        return true;
    }
}
