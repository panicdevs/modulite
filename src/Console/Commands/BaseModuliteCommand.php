<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;

/**
 * Base class for all Modulite make commands.
 *
 * Provides common functionality for interactive command generation
 * with module-aware file creation and management.
 */
abstract class BaseModuliteCommand extends GeneratorCommand
{
    /**
     * Get the available modules for selection.
     */
    protected function getAvailableModules(): array
    {
        if (!class_exists(Module::class)) {
            return [];
        }

        return collect(Module::all())
            ->map(fn($module) => $module->getName())
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get the available panels for selection.
     */
    protected function getAvailablePanels(): array
    {
        $panels = ['admin', 'manager', 'dashboard']; // Common panel names

        // Try to discover existing panels from config
        $configPanels = config('modulite.panels.locations', []);
        foreach ($configPanels as $location) {
            if (preg_match('/\{panel\}/', $location)) {
                // This location uses panel placeholders, we need to discover actual panels
                $discovered = $this->discoverExistingPanels();
                $panels = array_merge($panels, $discovered);
            }
        }

        return array_unique($panels);
    }

    /**
     * Discover existing panels from the filesystem.
     */
    protected function discoverExistingPanels(): array
    {
        $panels = [];
        $modules = $this->getAvailableModules();

        foreach ($modules as $module) {
            $filamentPath = base_path("modules/{$module}/Filament");

            if (File::exists($filamentPath)) {
                $directories = File::directories($filamentPath);
                foreach ($directories as $dir) {
                    $panelName = mb_strtolower(basename($dir));
                    if (!in_array($panelName, $panels)) {
                        $panels[] = $panelName;
                    }
                }
            }
        }

        return $panels;
    }

    /**
     * Interactively select a module.
     */
    protected function selectModule(): string
    {
        $modules = $this->getAvailableModules();

        if (empty($modules)) {
            $this->error('No modules found! Please create a module first using: php artisan module:make <ModuleName>');
            exit(1);
        }

        return $this->choice('Which module should contain this component?', $modules);
    }

    /**
     * Interactively select a panel.
     */
    protected function selectPanel(): string
    {
        $panels = $this->getAvailablePanels();

        $selected = $this->choice('Which panel should this component belong to?', $panels);

        // Ask if they want to create a new panel if they don't see what they want
        if ($this->confirm('Don\'t see the panel you want? Would you like to create a new one?', false)) {
            $selected = $this->ask('Enter the new panel name');
        }

        return mb_strtolower($selected);
    }

    /**
     * Get the base path for a module.
     */
    protected function getModulePath(string $module): string
    {
        return base_path("modules/{$module}");
    }

    /**
     * Get the Filament component path for a module and panel.
     */
    protected function getFilamentPath(string $module, string $panel, string $type): string
    {
        return $this->getModulePath($module)."/Filament/".Str::studly($panel)."/".Str::studly($type);
    }

    /**
     * Get the namespace for a Filament component.
     */
    protected function getFilamentNamespace(string $module, string $panel, string $type): string
    {
        return "Modules\\{$module}\\Filament\\".Str::studly($panel)."\\".Str::studly($type);
    }

    /**
     * Ensure the target directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            $this->info("Created directory: {$path}");
        }
    }

    /**
     * Get the stub file content and replace placeholders.
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);
    }

    /**
     * Replace additional placeholders in the stub.
     */
    protected function replaceAdditionalPlaceholders(string $stub, array $replacements): string
    {
        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $stub;
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(string $type, string $name, string $path): void
    {
        $this->info("✅ {$type} created successfully!");
        $this->line("📁 Location: {$path}");
        $this->line("🎯 Class: {$name}");

        $this->newLine();
        $this->comment('Next steps:');
        $this->line('• Customize the component to fit your needs');
        $this->line('• The component will be automatically discovered by Modulite');
        $this->line('• Run `php artisan modulite:status` to verify registration');
    }

    /**
     * Check if a file already exists and ask for confirmation to overwrite.
     */
    protected function confirmOverwrite(string $path): bool
    {
        if (File::exists($path)) {
            return $this->confirm("The file {$path} already exists. Do you want to overwrite it?", false);
        }

        return true;
    }

    /**
     * Get user input with validation.
     */
    protected function askWithValidation(string $question, ?callable $validator = null): string
    {
        do {
            $answer = $this->ask($question);

            if ($validator && !$validator($answer)) {
                $this->error('Invalid input. Please try again.');
                continue;
            }

            return $answer;
        } while (true);
    }

    /**
     * Validate class name.
     */
    protected function validateClassName(string $name): bool
    {
        return (bool)preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name);
    }

    /**
     * Create related view file if needed.
     */
    protected function createViewFile(string $module, string $panel, string $componentName): void
    {
        $viewName = Str::kebab($componentName);
        $viewPath = $this->getModulePath($module)."/Resources/views/filament/".mb_strtolower($panel)."/pages/{$viewName}.blade.php";

        if ($this->confirm("Would you like to create a corresponding view file?", true)) {
            $this->ensureDirectoryExists(dirname($viewPath));

            $viewContent = "<div>\n    <h1>{{ \$this->getTitle() }}</h1>\n    {{-- Your page content here --}}\n</div>\n";

            File::put($viewPath, $viewContent);
            $this->line("📄 View created: {$viewPath}");
        }
    }

    /**
     * Get component type specific configurations.
     */
    abstract protected function getComponentType(): string;

    /**
     * Get additional interactive inputs specific to component type.
     */
    protected function getAdditionalInputs(): array
    {
        return [];
    }
}
