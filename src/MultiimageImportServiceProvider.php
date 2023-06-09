<?php

namespace Taitin\MultiimageImport;

use Dcat\Admin\Admin;
use Illuminate\Support\ServiceProvider;

class MultiimageImportServiceProvider extends ServiceProvider
{
    protected $js = [
        'js/index.js',

    ];
    protected $css = [
        'css/index.css',
    ];

    public function register()
    {
        //
    }

    public function init()
    {
        parent::init();


        //

    }

    public function boot()
    {
        $this->loadTranslationsFrom(dirname(__DIR__) . '/resources/lang', 'multiimage-import');
    }
    public function settingForm()
    {
        return new Setting($this);
    }
}
