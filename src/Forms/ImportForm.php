<?php

namespace Taitin\MultiimageImport\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Taitin\MultiimageImport\Imports\MultiImageImport;
use Taitin\MultiimageImport\MultiimageImportServiceProvider;

class ImportForm extends Form implements LazyRenderable
{
    use LazyWidget;
    protected $selector = '.import-class';
    protected $import_class;
    protected $finish_url = "/finish_import_redirect";
    protected $sample_url = "/sample_url";
    protected $import_path = "import_temp";
    protected $id;
    public function setId($value = 0)
    {
        if (!empty($value)) {
            $this->id = $value;
            session(['import_id' => $this->id]);
        } else {
            $this->id = session('import_id', 0);
        }
        return $this;
    }
    public function setImportClass(MultiImageImport $import_class)
    {
        session(['import_class' => $import_class]);
        return $this;
    }

    public function setSampleUrl($value)
    {
        session(['sample_url' => $value]);
        $this->sample_url = $value;
        return $this;
    }
    public function handle(array $request)
    {

        // $request ...
        // 下面的程式碼獲取到上傳的檔案，然後使用`maatwebsite/excel`等包來處理上傳你的檔案，儲存到資料庫
        try {
            $id = $request['id'];
            $import =   session('import_class', false);
            if ($import === false) throw ('You need to set Import class');
            $files = $request['files'];;
            $import_files = [];
            $import->setImportPath($this->import_path . '/' . $id . '/files/');
            foreach ($files as $file) {
                $name = str_replace($this->import_path . '/' . $id . '/files/', '', $file);
                $import_files[$name] = $name;
            }
            $import->setFiles($import_files);
            $i = 1;
            $columns = $import->columns;
            $import->import(public_path('uploads/' . $request['import_file']));
            $str = '';

            foreach ($import->failures() as $failure) {
                $str .=  ' 第' . $failure->row() . '行 欄' . chr(65 + $failure->attribute()) . ':『' .  $columns[$failure->attribute()] . '』=>' . $failure->values()[$failure->attribute()] . ' 失敗原因：' . implode(' ', $failure->errors()) . '<br>';
            }

            File::deleteDirectory($this->import_path . '/' . $id);
            if ($str != '')
                return $this->response()->error($str)->refresh();
            else  return $this->response()->success('匯入成功')->refresh();

            //return   response()->json(['result' => true]);
        } catch (Exception $e) {
            // session()->flash('error', $e->getMessage());

            $str =   $e->getMessage();
            return $this->response()->error($str);
        }
    }
    public function form()
    {
        if ($this->sample_url != '')       $this->html('<a target="_blank" href="' . session('sample_url', $this->sample_url) . '" class="btn btn-primary ml-1"><i class="feather icon-download"></i>下載範本</a>');
        $this->setId(0);
        $id = $this->id;
        $this->hidden('id')->default($id);
        $this->file('import_file', '請選擇匯入檔案(excel)')->autoUpload()
            ->move($this->import_path . '/' . $id . '/import');
        $this->multipleFile('files', '上傳檔案')
            ->autoUpload()
            ->move($this->import_path . '/' . $id . '/files');
    }



    //     public function html()
    //     {
    //         return <<<HTML
    //         <a class="btn btn-sm btn-success import-class"><i class="fa fa-upload" aria-hidden="true"></i>&nbsp;&nbsp;匯入資料</a>
    // HTML;
    //     }
}
