<?php

namespace Zainiklab\ZaiInstaller\Http\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\SQLiteConnection;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseManager
{


    // public function migrateAndSeed()
    // {
    //     try {
    //         Artisan::call('migrate:fresh', ['--force'=> true]);
    //         Artisan::call('db:seed', ['--force' => true]);
    //         Artisan::call('storage:link');
    //     } catch (\Exception $e) {
    //         return $this->response($e->getMessage(), 'error');
    //     }
    //     return $this->response('Seed Complete', 'success');

    // }
    public function migrateAndSeed()
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            if (function_exists('symlink')) {
                Artisan::call('storage:link');
            } else {
                $this->copyFolder(storage_path('app/public'), public_path()."/storage/");
                EnvManager::setValue("IS_SYMLINK_SUPPORT", "false");
            }
        } catch (\Exception $e) {
            return $this->response($e->getMessage(), 'error');
        }
        return $this->response('Seed Complete', 'success');

    }

    function copyFolder($source, $destination)
    {
        if (is_dir($source)) {
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true); // Create the destination directory if it doesn't exist
            }

            $dir = opendir($source);

            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    $src = $source . '/' . $file;
                    $dest = $destination . '/' . $file;

                    if (is_dir($src)) {
                        // If it's a directory, recursively call the function
                        copyFolder($src, $dest);
                    } else {
                        // If it's a file, use copy() to copy it
                        copy($src, $dest);
                    }
                }
            }

            closedir($dir);
        } else {
            // If the source is a file, use copy() to copy it
            copy($source, $destination);
        }
    }


    /**
     * Run the migration and call the seeder.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function migrate(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true], $outputLog);
        } catch (\Exception $e) {
            return $this->response('Migration not Complete', 'error', $outputLog);
        }
        return $this->response('Seed Complete', 'success', $outputLog);

    }

    /**
     * Seed the database.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function seed(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('db:seed', ['--force' => true], $outputLog);
        } catch (\Exception $e) {
            return $this->response('Seeding not Complete', 'error', $outputLog);
        }

        return $this->response('Seed Complete', 'success', $outputLog);
    }

    /**
     * Return a formatted error messages.
     *
     * @param string $message
     * @param string $status
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function response($message, $status)
    {
        return [
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * Check database type. If SQLite, then create the database file.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     */
    private function sqlite(BufferedOutput $outputLog)
    {
        if (DB::connection() instanceof SQLiteConnection) {
            $database = DB::connection()->getDatabaseName();
            if (!file_exists($database)) {
                touch($database);
                DB::reconnect(Config::get('database.default'));
            }
            $outputLog->write('Using SqlLite database: ' . $database, 1);
        }
    }
}
