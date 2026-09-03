<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AccountImportTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(Request $request, string $type): BinaryFileResponse
    {
        validator(['type' => $type], ['type' => ['required', Rule::in(['siswa', 'guru'])]])->validate();

        return Excel::download(new AccountImportTemplateExport($type), "template-import-{$type}.xlsx");
    }
}
