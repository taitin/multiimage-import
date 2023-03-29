<?php

namespace Taitin\MultiimageImport\Http\Controllers;

use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Taitin\MultiimageImport\Actions\ImportForm;
use Taitin\MultiimageImport\Imports\MultiImageImport;

class MultiimageImportController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Title')
            ->description('Description')
            ->body(Admin::view('taitin.multiimage-import::index'));
    }


    protected function filesHandle(Request $request)
    {
        try {
            $request->file('file');
            $files = $request->session()->pull('files');;
            if (!empty($request->file('files')))
                foreach ($request->file('files') as $each) {

                    $path = $each->store(config('admin.upload.directory.image'), 'admin');
                    // $path = $this->imageFormat($path);
                    $name = $each->getClientOriginalName();

                    $files[$name] = $path;
                }

            $request->session()->put('files', $files);

            return response()->json(['result' => true, 'files' => $files]);
        } catch (Exception $e) {
            return response()->json(['result' => FALSE, 'err' => $e->getMessage()]);
        }
    }


    public function batchHandle(Request $request)
    {

        $r =  (new ImportForm(new MultiImageImport()))->handle($request);
        if (empty($r))  return   response()->json(['result' => true]);
        else         return response()->json(['result' => FALSE, 'err' => $r]);
    }
}
