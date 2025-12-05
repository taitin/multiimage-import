<?php

namespace Taitin\MultiimageImport\Imports;

use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait ImportFunction
{

    public $import_path;
    public function setImportPath($import_path)
    {
        $this->import_path = $import_path;
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }
    public function TOF($value)
    {
        if ($value == '是') return 1;
        else return 0;
    }

    public function fileMove($r, $key = '')
    {
        $d = explode('.', $r);
        $ext = $d[1];
        $from  = public_path('storage/' . $this->import_path . $r);
        $to =   'images/' . time() . $key . '.' . $ext;
        
        // 檢查來源檔案是否存在
        if (!file_exists($from)) {
            Log::warning("Import file not found, skipping: {$from}");
            return ''; // 返回空字串,繼續處理其他檔案
        }
        
        while (file_exists(public_path('storage/' . $to))) {
            $key++;
            $to =   'images/' . time() . $key . '.' . $ext;
        }
        
        // 嘗試複製檔案,如果失敗則記錄錯誤並跳過
        try {
            if (!copy($from, public_path('storage/' . $to))) {
                throw new \Exception("Failed to copy file");
            }
            return $to;
        } catch (\Exception $e) {
            Log::error("Failed to copy file from {$from} to {$to}: " . $e->getMessage());
            return ''; // 返回空字串,繼續處理
        }
    }

    public function fileMap($name, $multi = false)
    {

        if ($multi) {
            $v = explode(',', $name);
            $result = [];
            foreach ($v as $key => $r) {
                if (isset($this->files[$r]) && $this->files[$r] != '') {
                    $result[] =  $this->fileMove($this->files[$r], $key);
                }
            }
            return  $result;
        } else {


            if (isset($this->files[$name]) && $this->files[$name] != '') {
                return $this->fileMove($name);
            } else {
                return '';
            }
        }
    }

    public function findOptionKey($options, $value, $multi = false)
    {
        if (is_object($options)) $options = $options->toArray();
        if ($multi) {
            $result = [];
            foreach (explode(',', $value) as $v) {
                $r =  array_search(trim($v), $options);
                if (!$r);
                else  $result[] = (string)$r;
            }
            return $result;
        } else {
            $r =  array_search(trim($value), $options);
            if (!$r) return key($options);
            return $r ?? '';
        }
    }
    // 自定義驗證資訊
    public function customValidationMessages()
    {

        foreach ($this->columns as $key => $column) {
            $result[$key . '.required'] = $column . __('Empty');
        }

        return $result;
    }


    /**
     * (false|string) str_to_date_format : 將 "2017年7月10日" "2017.7.10" 轉為標準的日期格式"2017-7-10"
     *
     * @param         $date
     * @param boolean $falseReturnNow 格式錯誤是否返回當前日期
     * @param string  $format
     *
     * @return false|string
     */
    function str_to_date_format($date, $falseReturnNow = true, $format = 'Y-m-d')
    {
        if (!$falseReturnNow && !$date) {
            return null;
        }
        // 接收的日期如果是數值型則用PHPExcel的內建方法轉換成時間戳
        if (is_numeric($date)) {
            $t = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            //按格式返回
            return $t->format($format);
        }
        $date = str_replace(['號', '日'], '', $date);
        $date = str_replace(['年', '月', '.', '—', '——', '/', '－', '－－'], '-', $date);
        // 如果時間格式錯誤，是否需要返回當前時間
        if (date($format, strtotime($date)) == '1970-01-01' && $falseReturnNow && DateTime::createFromFormat('Y-m-d G:i:s', $date) === FALSE) {
            return date($format);
        }
        return date($format, strtotime($date));
    }
}
