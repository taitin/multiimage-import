<?php

namespace Taitin\MultiimageImport\Tools;

use Dcat\Admin\Widgets\Modal;
use Taitin\MultiimageImport\Forms\ImportForm;
use Taitin\MultiimageImport\Imports\MultiImageImport;

class MultiImageImportTool
{

    public function __construct()
    {
    }

    public static function make(MultiImageImport $import, $sample_url = '', $title = '匯入檔案', $button = '匯入檔案', $with_files = true)
    {

        $import_form = new ImportForm($with_files);
        $import_form->setId(time())
            ->setImportClass($import)
            ->setSampleUrl($sample_url)
            ->withFiles($with_files);

        $modal = Modal::make()
            ->lg()
            ->title($title)
            ->body($import_form)
            ->button('<a class="btn btn-primary ml-1"><i class="feather icon-upload"></i> ' . $button . '</a>');
        return $modal;
    }
}
