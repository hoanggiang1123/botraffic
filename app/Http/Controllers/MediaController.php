<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index (Request $request) {
        if ($request->hasFile('picture')) {

            $name = uniqid() . '_' . time() . '.' . $request->file('picture')->getClientOriginalExtension();

            $path = $request->file('picture')->storeAs('public/Media', $name);

            if ($path) return response(['picture' => Storage::url($path)]);

            return response(['picture' => null]);
        }
    }
}
