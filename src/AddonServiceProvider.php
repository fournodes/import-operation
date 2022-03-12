<?php

namespace Fournodes\ImportOperation;

use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    use AutomaticServiceProvider;

    protected $vendorName = 'fournodes';
    protected $packageName = 'import-operation';
    protected $commands = [];
}
