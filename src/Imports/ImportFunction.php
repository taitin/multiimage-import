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
        $from  = public_path('uploads/' . $this->import_path . $r);
        $to =   'images/' . time() . $key . '.' . $ext;
        copy($from, public_path('uploads/' . $to));
        return $to;
    }

    public function fileMap($name, $multi = false)
    {

        if ($multi) {
            $v = explode(',', $name);
            $result = [];
            foreach ($v as $key => $r) {
                if (isset($this->files[$r]) && $this->files[$r] != '') {
                    $result[] =  $this->fileMove($r, $key);
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
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
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
