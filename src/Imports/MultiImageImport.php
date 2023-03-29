<?php

namespace Taitin\MultiimageImport\Imports;


use App\Rules\HostFormat;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MultiImageImport implements ToModel, WithStartRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures, ImportFunction;

    public $columns =  [
        '0' => '部門',
        '1' => '主管職否',
        '2' => '職稱',
        '3' => '到職日(西元年)',
        '4' => '到職日(月)',
        '5' => '到職日(日)',
        '6' => '工作地點',
        '7' => '外派與否',
        '8' => '性別',
        '9' => '生日(西元年)',
        '10' => '生日(月)',

    ];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        //Sample
        // $company = new Company();
        // $new_company =  new Company([
        //     'is_student' => $this->findOptionKey($company->is_student_options, $row[4]),
        //     'joined_plan' =>  $this->findOptionKey(Project::where('type', 0)->get()->pluck('name', 'id'), $row[8], true),
        //     'company_created_at' => $this->str_to_date_format($row[15]),
        //     'last_financial_statements' => $this->fileMap($row[35], true),
        //     'in_NCKU' => $this->TOF($row[41]),
        // ]);
        // return $new_company;
    }


    // 從2行開始讀取資料
    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [];
        // return [
        //     '1' => 'required',
        //     '2' => [new HostFormat()],
        //     '4' => 'required',
        //     '5' => 'required',
        //     '16' => 'required',
        //     '18' => 'required',
        //     '19' => 'required',
        //     '20' => 'required',
        //     '23' => 'required',
        //     '24' => 'required',
        //     '31' => 'required',
        //     '32' => 'required',
        //     '33' => 'required',
        //     '41' => 'required',
        //     '43' => 'required'
        // ];
    }
}
