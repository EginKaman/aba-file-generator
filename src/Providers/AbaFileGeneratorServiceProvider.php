<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Providers;

use EginKaman\AbaFileGenerator\Contracts\Transaction as TransactionContract;
use EginKaman\AbaFileGenerator\Generator\AbaFileGenerator;
use EginKaman\AbaFileGenerator\Transaction;
use Illuminate\Support\ServiceProvider;

class AbaFileGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/aba-generator.php' => config_path('aba-generator.php'),
        ], 'aba-generator');

        $this->app->bind(
            'aba-generator',
            fn ($app) => new AbaFileGenerator(
                $app['config']->get('aba-generator.bsb'),
                $app['config']->get('aba-generator.account_number'),
                $app['config']->get('aba-generator.bank_name'),
                $app['config']->get('aba-generator.user_name'),
                $app['config']->get('aba-generator.remitter'),
                $app['config']->get('aba-generator.direct_entry_id'),
                'Payroll',
            )
        );

        $this->app->singleton(TransactionContract::class, Transaction::class);
    }

    public function boot(): void
    {
    }
}
