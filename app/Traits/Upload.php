<?php
namespace App\Traits;
use Illuminate\Support\Facades\Storage;

trait Upload {

    public function upLoadFileSingle ($folder, $field) {

        if (request()->hasFile($field)) {

            $name = uniqid() . '_' . time() . '.' . request()->file($field)->getClientOriginalExtension();

            $path = request()->file('image')->storeAs('public/' . $folder, $name);

            if ($path) return Storage::url($path);

            return null;
        }

    }
}