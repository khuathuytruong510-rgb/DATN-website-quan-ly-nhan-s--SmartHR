<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractTemplateFormRequest;
use App\Models\ContractTemplate;
use App\Models\ContractClause;
use App\Support\ContractFixedTerms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractTemplateController extends Controller
{
    public function index(): View
    {
        return view('contracts.templates.index', [
            'templates' => ContractTemplate::latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('contracts.templates.form', [
            'template' => new ContractTemplate(),
            'isEdit' => false,
            'clauses' => ContractClause::where('status', 'active')->ordered()->get(),
        ]);
    }

    public function store(ContractTemplateFormRequest $request): RedirectResponse
    {
        if ($request->boolean('is_default')) {
            ContractTemplate::where('contract_type', $request->input('contract_type'))
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        ContractTemplate::create($request->validated());

        return redirect()->route('contract-templates.index')->with('success', 'Tạo mẫu hợp đồng thành công.');
    }

    public function edit(ContractTemplate $template): View
    {
        return view('contracts.templates.form', [
            'template' => $template,
            'isEdit' => true,
            'clauses' => ContractClause::where('status', 'active')->ordered()->get(),
        ]);
    }

    public function update(ContractTemplateFormRequest $request, ContractTemplate $template): RedirectResponse
    {
        if ($request->boolean('is_default')) {
            ContractTemplate::where('contract_type', $request->input('contract_type'))
                ->where('is_default', true)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($request->validated());

        return redirect()->route('contract-templates.index')->with('success', 'Cập nhật mẫu hợp đồng thành công.');
    }

    public function destroy(ContractTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('contract-templates.index')->with('success', 'Xóa mẫu hợp đồng thành công.');
    }

    public function templateContent(Request $request): JsonResponse
    {
        $contractType = $request->query('contract_type');

        $template = ContractTemplate::query()
            ->active()
            ->when($contractType, function ($query, $value) {
                $query->where('contract_type', $value);
            })
            ->where('is_default', true)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'template_id' => optional($template)->id,
            'content' => optional($template)->content ?? ContractFixedTerms::forType($contractType),
        ]);
    }
}
