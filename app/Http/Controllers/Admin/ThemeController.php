<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Services\Theme\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Danh sách themes
     */
    public function index()
    {
        $themes = $this->themeService->getAllThemes();
        return view('admin.themes.index', compact('themes'));
    }

    /**
     * Form tạo theme mới
     */
    public function create()
    {
        $defaultVariables = $this->themeService->getDefaultVariables();
        $theme = null;
        return view('admin.themes.form', compact('defaultVariables', 'theme'));
    }

    /**
     * Lưu theme mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $variables = $this->buildVariablesFromRequest($request);

        $this->themeService->createTheme([
            'name' => $request->name,
            'description' => $request->description,
            'variables' => $variables,
            'custom_css' => $request->custom_css,
        ]);

        return redirect()->route('admin.themes.index')->with('success', 'Tạo theme thành công!');
    }

    /**
     * Form chỉnh sửa theme
     */
    public function edit(Theme $theme)
    {
        $defaultVariables = $this->themeService->getDefaultVariables();
        return view('admin.themes.form', compact('theme', 'defaultVariables'));
    }

    /**
     * Cập nhật theme
     */
    public function update(Request $request, Theme $theme)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $variables = $this->buildVariablesFromRequest($request);

        $this->themeService->updateTheme($theme->id, [
            'name' => $request->name,
            'description' => $request->description,
            'variables' => $variables,
            'custom_css' => $request->custom_css,
        ]);

        return redirect()->route('admin.themes.index')->with('success', 'Cập nhật theme thành công!');
    }

    /**
     * Xoá theme
     */
    public function destroy(Theme $theme)
    {
        try {
            $this->themeService->deleteTheme($theme->id);
            return redirect()->route('admin.themes.index')->with('success', 'Xoá theme thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kích hoạt theme
     */
    public function activate(Theme $theme)
    {
        $this->themeService->activateTheme($theme->id);
        return redirect()->route('admin.themes.index')->with('success', "Đã kích hoạt theme \"{$theme->name}\"!");
    }

    /**
     * Preview CSS (AJAX)
     */
    public function previewCss(Theme $theme)
    {
        return response($theme->generateFullCss(), 200)
            ->header('Content-Type', 'text/css');
    }

    /**
     * Build variables array từ form request
     */
    private function buildVariablesFromRequest(Request $request): array
    {
        $defaultVariables = $this->themeService->getDefaultVariables();
        $variables = [];

        foreach ($defaultVariables as $key => $defaultValue) {
            $inputKey = 'var_' . $key;
            $variables[$key] = $request->input($inputKey, $defaultValue);
        }

        return $variables;
    }
}
