<?php

namespace Taitin\MultiimageImport\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
    protected $with_files = true;

    public function withFiles($with_files = true)
    {
        session(['with_files' => $with_files]);
        return $this;
    }


    public function setId($value = 0)
    {
        // 明確檢查是否為有效的非零值
        if ($value !== 0 && $value !== '0' && !empty($value)) {
            $this->id = $value;
            session(['import_id' => $this->id]);
        } else {
            // 總是生成新的唯一 ID（時間戳記 + 微秒）
            $this->id = time() . substr(microtime(), 2, 6);
            session(['import_id' => $this->id]);
        }
        return $this;
    }

    public function setImportPath($import_path)
    {
        session(['import_path' => $import_path]);
        return $this;
    }


    public function setSampleUrl($value)
    {
        session(['sample_url' => $value]);
        $this->sample_url = $value;
        return $this;
    }
    public function  getAllDirData($dir)
    {

        $import_files = [];
        $handle = @opendir($dir) or die('Cannot open' . $dir);
        while ($file = readdir($handle)) {
            if ($file != '.' && $file != '..') {

                if (is_dir($dir . '/' . $file)) {
                    $d = $this->getAllDirData($dir . '/' . $file);
                    $import_files = array_merge($d, $import_files);
                } else {
                    $name = $dir . '/' . $file;
                    $import_files[$name] = $name;
                }
            }
        }
        return $import_files;
    }



    public function handle(array $request)
    {

        // $request ...
        // 下面的程式碼獲取到上傳的檔案，然後使用`maatwebsite/excel`等包來處理上傳你的檔案，儲存到資料庫
        try {
            $id = $request['id'];
            $className = $this->payload['import_class'] ?? session('import_class', null);
            if (!class_exists($className)) {
                throw new \Exception('Import class not found.');
            }

            $import = app($className); // ← 正確方式：用 class 名稱 new 出物件

            if (! $import instanceof \Taitin\MultiimageImport\Imports\MultiImageImport) {
                throw new \Exception('Import class must extend MultiImageImport.');
            }
            $files = $request['files'] ?? [];
            $import_files = [];
            $this->import_path = $this->payload['import_path'] ?? session('import_path', $this->import_path);
            $dir = $this->import_path . '/' . $id . '/files/';
            $zip_path = public_path('storage/' . $dir . 'zip');
            // $import->setImportPath($dir);
            foreach ($files as $file) {
                $name = str_replace($dir, '', $file);
                if (str_contains($file, '.zip')) {
                    $zip = new \ZipArchive();
                    // $file 已經是完整的相對路徑,直接使用
                    $zipFile = public_path('storage/' . $file);
                    
                    // 檢查 ZIP 檔案是否存在
                    if (!file_exists($zipFile)) {
                        throw new \Exception("ZIP file not found: {$zipFile}");
                    }
                    
                    // 嘗試開啟 ZIP 檔案
                    $openResult = $zip->open($zipFile);
                    if ($openResult !== true) {
                        // 提供更詳細的錯誤訊息
                        $errorMessages = [
                            \ZipArchive::ER_EXISTS => 'File already exists',
                            \ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                            \ZipArchive::ER_INVAL => 'Invalid argument',
                            \ZipArchive::ER_MEMORY => 'Malloc failure',
                            \ZipArchive::ER_NOENT => 'No such file',
                            \ZipArchive::ER_NOZIP => 'Not a zip archive',
                            \ZipArchive::ER_OPEN => 'Can\'t open file',
                            \ZipArchive::ER_READ => 'Read error',
                            \ZipArchive::ER_SEEK => 'Seek error',
                        ];
                        $errorMsg = $errorMessages[$openResult] ?? 'Unknown error';
                        throw new \Exception("Failed to open ZIP file '{$name}': {$errorMsg} (Error code: {$openResult})");
                    }
                    
                    // 建立解壓縮目錄
                    if (!File::isDirectory($zip_path)) {
                        File::makeDirectory($zip_path, 0755, true);
                    }
                    
                    // 解壓縮檔案
                    if (!$zip->extractTo($zip_path)) {
                        $zip->close();
                        throw new \Exception("Failed to extract ZIP file: " . $name);
                    }
                    
                    $zip->close();
                } else {
                    $import_files[$name] = $name;
                }
            }


            if (is_dir($zip_path)) {
                $files = $this->getAllDirData($zip_path);

                $dir = public_path('storage/' . $this->import_path . '/' . $id . '/files/zip/');
                foreach ($files as $file) {
                    $name = str_replace($dir, '', $file);
                    $r = explode('/', $name);
                    $index = $r[count($r) - 1];
                    $import_files[$index] = 'zip/' . $name;
                }
            }

            $import->setFiles($import_files);
            $i = 1;
            $columns = $import->columns;

            $import->import(public_path('storage/' . $request['import_file']));
            $str = '';

            $last_line = '';
            foreach ($import->failures() as $failure) {
                $line =  ' ' . __('multiimage-import::import.No') . $failure->row() . __('multiimage-import::import.Row') . ' ' . __('multiimage-import::import.Column') . chr(65 + $failure->attribute()) . ':『' .  $columns[$failure->attribute()] . '』=>' . $failure->values()[$failure->attribute()] . ' ' . __('Fail reason') . implode(' ', $failure->errors()) . '<br>';
                if ($last_line != $line || 1) $str .= $line;
                $last_line = $line;
            }

            $version = Admin::VERSION;
            if ($str != '') {
                if (version_compare($version, '2.0', '>=')) {
                    return $this->response()->error($str)->refresh();
                }
                return $this->error($str);
            } else {
                File::deleteDirectory(public_path('storage/' . $this->import_path . '/' . $id));
                if (version_compare($version, '2.0', '>=')) {
                    return $this->response()->success(__('multiimage-import::import.Import_success'))->refresh();
                }
                return $this->success(__('multiimage-import::import.Import_success'));
            }

            //return   response()->json(['result' => true]);
        } catch (Exception $e) {
            // session()->flash('error', $e->getMessage());
            $version = Admin::VERSION;
            $str =   $e->getMessage();
            if (version_compare($version, '2.0', '>=')) {
                return $this->response()->error($str)->refresh();
            }
            return $this->error($str);
        }
    }
    public function form()
    {
        // 清除可能存在的舊 session,確保每次都使用新的 ID
        session()->forget('import_id');
        
        $this->sample_url = request()->input('sample_url');
        // if ($this->sample_url != '')   $this->html('<a target="_blank" href="' . session('sample_url', $this->sample_url) . '" class="btn btn-primary ml-1"><i class="feather icon-download"></i>' . __('multiimage-import::import.Download example') . '</a>');
        $this->html('<a target="_blank" href="' . $this->sample_url . '" class="btn btn-primary ml-1"><i class="feather icon-download"></i>' . __('multiimage-import::import.Download example') . '</a>');

        // 永遠生成新的唯一 ID,避免多使用者衝突
        $uniqueId = time() . substr(microtime(), 2, 6);
        $this->setId($uniqueId);
        $id = $this->id;
        
        // 清理超過 24 小時的舊臨時目錄
        $this->cleanOldDirectories();
        $this->hidden('id')->default($id);
        $this->import_path = request()->input('import_path', $this->import_path);

        $this->file('import_file', __('multiimage-import::import.Select File'))->autoUpload()
            ->move($this->import_path . '/' . $id . '/import');
        $this->with_files =  request()->input('with_files', $this->with_files);
        if ($this->with_files) {
            $this->multipleFile('files', __('multiimage-import::import.Upload_files'))
                ->autoUpload()
                ->limit(100)
                ->move($this->import_path . '/' . $id . '/files');
        }
    }



    /**
     * 清理超過 24 小時的臨時目錄
     * 防止臨時檔案佔用過多儲存空間
     */
    protected function cleanOldDirectories()
    {
        try {
            $basePath = public_path('storage/' . $this->import_path);
            
            // 如果基礎路徑不存在,直接返回
            if (!File::isDirectory($basePath)) {
                return;
            }
            
            $cutoffTime = time() - 86400; // 24 小時前的時間戳記
            
            foreach (File::directories($basePath) as $dir) {
                $dirName = basename($dir);
                
                // 只處理數字命名的目錄(時間戳記目錄)
                if (is_numeric($dirName)) {
                    // 提取目錄名稱中的時間戳記部分(前10位)
                    $dirTimestamp = intval(substr($dirName, 0, 10));
                    
                    // 如果目錄建立時間超過 24 小時,刪除它
                    if ($dirTimestamp < $cutoffTime) {
                        File::deleteDirectory($dir);
                        Log::info('Cleaned old import directory: ' . $dirName);
                    }
                }
            }
        } catch (Exception $e) {
            // 記錄錯誤但不中斷流程
            Log::warning('Failed to clean old import directories: ' . $e->getMessage());
        }
    }

    //     public function html()
    //     {
    //         return <<<HTML
    //         <a class="btn btn-sm btn-success import-class"><i class="fa fa-upload" aria-hidden="true"></i>&nbsp;&nbsp;匯入資料</a>
// HTML;
    //     }
}