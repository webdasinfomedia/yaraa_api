<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class TagController extends Controller
{
    public function list()
    {
        try{
            $this->_response = [
                "error" => false,
                "message" => null,
                "data" => Tag::where('name','!=','')->pluck('name'),
            ];
            return response()->json($this->_response);
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
