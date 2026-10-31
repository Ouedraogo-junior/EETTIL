<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : Nom du module, ex. Clients}';

    protected $description = 'Crée la structure d\'un module dans app/Modules';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $base = app_path("Modules/{$name}");

        if (is_dir($base)) {
            $this->error("Le module {$name} existe déjà.");

            return self::FAILURE;
        }

        foreach (['Actions', 'Enums', 'Http/Controllers', 'Http/Requests', 'Http/Resources', 'Models', 'Policies', 'Tests', 'database/migrations'] as $dir) {
            File::ensureDirectoryExists("{$base}/{$dir}");
            File::put("{$base}/{$dir}/.gitkeep", '');
        }

        $provider = <<<'PHP'
<?php

namespace App\Modules\{{name}};

use App\Modules\ModuleServiceProvider;

class {{name}}ModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return '{{name}}';
    }
}
PHP;
        File::put("{$base}/{$name}ModuleServiceProvider.php", str_replace('{{name}}', $name, $provider));

        File::put("{$base}/routes.php", "<?php\n\n// Routes de l'API du module {$name} (préfixe /api/v1, middleware api).\n");

        $this->info("Module {$name} créé. Ajoute-le dans bootstrap/providers.php :");
        $this->line("    App\\Modules\\{$name}\\{$name}ModuleServiceProvider::class,");

        return self::SUCCESS;
    }
}