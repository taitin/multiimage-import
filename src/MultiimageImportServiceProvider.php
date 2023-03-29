<?php

namespace Taitin\MultiimageImport;

use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Admin;

class MultiimageImportServiceProvider extends ServiceProvider
{
    protected $js = [
        'js/index.js',
        'js/batch.js',

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

    public function settingForm()
    {
        return new Setting($this);
    }
}
