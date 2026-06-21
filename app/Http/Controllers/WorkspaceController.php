<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class WorkspaceController extends Controller
{

    public function show(Request $request, $uuid)
    {

        $workspace = Workspace::where('uuid', $uuid)
            ->with(["products", "categories","collections"])
            ->first();

        if (!is_null($workspace)) {
            $request->session()->put('workspace_uuid', $workspace->uuid);


            if (!$workspace->access_token) {
                $workspace->generateAccessToken();
            }

            Inertia::share(["workspace_uuid" => $workspace->uuid]);
            return Inertia::render('Workspace', [
                'item' => $workspace,
            ]);
        }

        // Создаём новую доску
        $workspace = Workspace::create([
            'uuid' => Str::uuid(),
        ]);
        $request->session()->put('workspace_uuid', $workspace->uuid);
        $workspace->generateAccessToken();


        return redirect('/workspace/' . $workspace->uuid);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Workspace $workspace)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workspace $workspace)
    {
        //
    }

    public function exportExcel(Request $request)
    {
        $workspace = $request->workspace;

        return Excel::download(
            new ProductsExport($workspace->id),
            'products.xlsx'
        );
    }
}
