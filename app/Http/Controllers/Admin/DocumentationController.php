<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\DocumentationService;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentationController extends Controller
{
    protected $documentation_service;

    public function __construct(DocumentationService $documentation_service)
    {
        $this->middleware('permission:documentation.view')->only(['index']);
        $this->middleware('permission:documentation.business.view')->only(['business']);
        $this->middleware('permission:documentation.developer.view')->only(['developer']);
        $this->middleware('permission:documentation.business.pdf')->only(['businessPdf']);
        $this->middleware('permission:documentation.developer.pdf')->only(['developerPdf']);

        $this->documentation_service = $documentation_service;
    }

    public function index()
    {
        $business_sections = $this->documentation_service->sections('business');
        $developer_sections = $this->documentation_service->sections('developer');

        return view('admin.documentation.index', compact('business_sections', 'developer_sections'));
    }

    public function business(?string $section = null)
    {
        $data = $this->documentation_service->render('business', $section);
        $data['audience'] = 'business';
        $data['audience_label'] = 'Business Documentation';

        return view('admin.documentation.reader', $data);
    }

    public function developer(?string $section = null)
    {
        $data = $this->documentation_service->render('developer', $section);
        $data['audience'] = 'developer';
        $data['audience_label'] = 'Developer Documentation';

        return view('admin.documentation.reader', $data);
    }

    public function businessPdf()
    {
        $title = 'Business Documentation';
        $sections = $this->documentation_service->renderAll('business');

        return Pdf::loadView('admin.documentation.pdf', compact('title', 'sections'))
            ->setPaper('a4', 'portrait')
            ->stream('business-documentation.pdf');
    }

    public function developerPdf()
    {
        $title = 'Developer Documentation';
        $sections = $this->documentation_service->renderAll('developer');

        return Pdf::loadView('admin.documentation.pdf', compact('title', 'sections'))
            ->setPaper('a4', 'portrait')
            ->stream('developer-documentation.pdf');
    }
}
