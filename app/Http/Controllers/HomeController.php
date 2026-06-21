<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Если доска уже есть — отправляем туда
        if ($request->session()->has('workspace_uuid')) {
            $uuid = $request->session()->get('workspace_uuid');
            $workspace = Workspace::query()
                ->with(["products","categories", "collections"])
                ->where("uuid", $uuid)
                ->first();

            if (!is_null($workspace))
                return redirect('/workspace/' . $uuid);
        }

        // Создаём пустую доску
        $workspace = Workspace::create([
            'uuid' => Str::uuid(),
        ]);

        // Сохраняем UUID в сессию
        $request->session()->put('workspace_uuid', $workspace->uuid);

        return redirect('/workspace/' . $workspace->uuid );
    }

    public function newSession(Request $request)
    {
        // Создаём пустую доску
        $workspace = Workspace::create([
            'uuid' => Str::uuid(),
        ]);
        // Сохраняем UUID в сессию
        //     $request->session()->put('workspace_uuid', $workspace->uuid);

        return redirect('/workspace/' . $workspace->uuid );
    }

    public function refreshSession(Request $request)
    {

        // Создаём пустую доску
        $workspace = $request->workspace;

        $workspace->uuid = Str::uuid();
        $workspace->save();


        return response()->json([
            "url"=>'/workspace/' . $workspace->uuid
        ]);
    }
}
