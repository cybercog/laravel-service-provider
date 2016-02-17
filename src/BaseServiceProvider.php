<?php

/*
 * This file is part of Laravel Service Provider.
 *
 * (c) CyberCog LLC <cybercog.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cog\ServiceProvider;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Class BaseServiceProvider.
 */
abstract class BaseServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $packageName;

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {

    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {

    }

    /**
     * Setup package paths.
     *
     * @param $path
     * @return $this
     */
    public function setup($path)
    {
        $this->paths = [
            'migrations' => [
                'src' => $path . '/../../database/migrations',
                'dest' => database_path('/migrations/%s_%s'),
            ],
            'seeds' => [
                'src' => $path . '/../../database/seeds',
                'dest' => database_path('/seeds/%s'),
            ],
            'config' => [
                'src' => $path . '/../../config',
                'dest' => config_path('%s'),
            ],
            'views' => [
                'src' => $path . '/../../resources/views',
                'dest' => base_path('resources/views/vendor/%s'),
            ],
            'translations' => [
                'src' => $path . '/../../resources/lang',
                'dest' => base_path('resources/lang/%s'),
            ],
            'assets' => [
                'src' => $path . '/../../public/assets',
                'dest' => public_path('vendor/%s'),
            ],
            'routes' => [
                'src' => $path . '/../Http/routes.php',
                'dest' => null,
            ],
        ];

        return $this;
    }

    /**
     * Publish configuration files.
     *
     * @param array $files
     * @return $this
     */
    protected function publishConfig(array $files = [])
    {
        if ($this->app->runningInConsole()) {
            $files = $this->buildFilesArray('config', $files);

            $paths = [];
            foreach ($files as $file) {
                $destPath = $this->buildDestPath('config', [$this->buildFileName($file)]);

                if (!File::exists($destPath)) {
                    $paths[$file] = $destPath;
                }
            }

            $this->publishes($paths, 'config');
        }

        return $this;
    }

    /**
     * Publish database migrations.
     *
     * @param array $files
     * @return $this
     */
    protected function publishMigrations(array $files = [])
    {
        if ($this->app->runningInConsole()) {
            $files = $this->buildFilesArray('migrations', $files);

            $paths = [];
            foreach ($files as $file) {
                if (!class_exists($this->getClassFromFile($file))) {
                    $fileDestination = $this->prepareMigrationFile($file);
                    $paths[$file] = $this->buildDestPath('migrations', [
                        date('Y_m_d_His', time()),
                        $this->buildFileName($fileDestination),
                    ]);
                }
            }

            $this->publishes($paths, 'migrations');
        }

        return $this;
    }

    /**
     * Publish view files.
     *
     * @return $this
     */
    protected function publishViews()
    {
        if ($this->app->runningInConsole()) {
            $destPath = $this->buildDestPath('views', $this->packageName);

            if (!File::exists($destPath)) {
                $this->publishes([
                    $this->paths['views']['src'] => $destPath,
                ], 'views');
            }
        }

        return $this;
    }

    /**
     * Publish assets.
     *
     * @return $this
     */
    protected function publishAssets()
    {
        if ($this->app->runningInConsole()) {
            $destPath = $this->buildDestPath('assets', $this->packageName);

            if (!File::exists($destPath)) {
                $this->publishes([
                    $this->paths['assets']['src'] => $destPath,
                ], 'public');
            }
        }

        return $this;
    }

    /**
     * Publish database seeds.
     *
     * @param array $files
     * @return $this
     */
    protected function publishSeeds(array $files = [])
    {
        if ($this->app->runningInConsole()) {
            $files = $this->buildFilesArray('seeds', $files);

            $paths = [];
            foreach ($files as $file) {
                $destPath = $this->buildDestPath('seeds', [$this->buildFileName($file)]);

                if (!File::exists($destPath)) {
                    $paths[$file] = $destPath;
                }
            }

            $this->publishes($paths, 'seeds');
        }

        return $this;
    }

    /**
     * Load views files.
     *
     * @return $this
     */
    protected function loadViews()
    {
        $this->loadViewsFrom($this->paths['views']['src'], $this->packageName);

        return $this;
    }

    /**
     * Load translations files.
     *
     * @return $this
     */
    protected function loadTranslations()
    {
        $this->loadTranslationsFrom(
            $this->paths['translations']['src'], $this->packageName
        );

        return $this;
    }

    /**
     * Load routes.
     *
     * @return $this
     */
    protected function loadRoutes()
    {
        if (!$this->app->routesAreCached()) {
            require $this->paths['routes']['src'];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function publish(array $paths, $group = null)
    {
        $this->publishes($paths, $group);

        return $this;
    }

    /**
     * Merge config files.
     *
     * @param $file
     * @return $this
     */
    protected function mergeConfig($file = null)
    {
        if (empty($file)) {
            $file = $this->packageName;
        }

        $this->mergeConfigFrom(
            $this->paths['config']['src'] . '/' . $this->buildFileName($file),
            $this->packageName
        );

        return $this;
    }

    /**
     * Prepare script filename.
     *
     * @param $file
     * @return string
     */
    private function buildFileName($file)
    {
        $file = basename($file);
        $file = $this->addPhpExtension($file);

        return $file;
    }

    /**
     * Prepare destination path.
     *
     * @param $type
     * @param $args
     * @return string
     */
    private function buildDestPath($type, $args)
    {
        return vsprintf($this->paths[$type]['dest'], $args);
    }

    /**
     * Collect an array of package files.
     *
     * @param $type
     * @param $files
     * @return array
     */
    private function buildFilesArray($type, $files)
    {
        $path = $this->paths[$type]['src'];

        if (empty($files)) {
            $files = [];

            foreach (glob($path . '/*.stub') as $file) {
                $files[] = $file;
            }
        } else {
            foreach ($files as $key => $value) {
                $files[] = $path . '/' . $this->buildFileName($value);
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * Parse PHP class name from file path.
     *
     * @param $path
     * @return mixed
     */
    private function getClassFromFile($path)
    {
        $count = count($tokens = token_get_all(file_get_contents($path)));

        for ($i = 2; $i < $count; ++$i) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                return $tokens[$i][1];
            }
        }
    }

    /**
     * Removing file version prefix & stub extension.
     *
     * @param $file
     * @return string
     */
    private function prepareMigrationFile($file)
    {
        $file = basename($file);

        if (preg_match('#[\d]{4}_(.+)#', $file)) {
            // Cutting database migration prefix
            $file = substr($file, 5);
        }

        if (ends_with($file, '.stub')) {
            $file = substr($file, 0, -5);
        }

        $file = $this->addPhpExtension($file);

        return $file;
    }

    /**
     * Add PHP file extension if not exist.
     *
     * @param $file
     * @return string
     */
    private function addPhpExtension($file)
    {
        if (!ends_with($file, '.php')) {
            $file = $file . '.php';
        }

        return $file;
    }
}
