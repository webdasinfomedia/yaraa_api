<?php

namespace App\Http\Controllers;

use App\Models\LogLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogLocationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $request->merge(['email' => auth()->user()->email]);

            LogLocation::updateOrCreate(
                ["email" => auth()->user()->email],
                $request->all(),
            );

            $this->setResponse(false, "Location updated");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emails' => 'required|array',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $emails = $request->emails;

            $locations = LogLocation::raw(function ($collection) use ($emails) {
                return $collection->aggregate([
                    [
                        '$sort' => [
                            'created_at' => -1
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$email',
                            'email' => ['$first' => '$email'],
                            'latitude' => ['$first' => '$latitude'],
                            'longitude' => ['$first' => '$longitude'],
                        ]
                    ],
                    [
                        '$match' => [
                            'email' => ['$in' => $emails]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'localField' => 'email',
                            'foreignField' => 'email',
                            'as' => 'users_detail'
                        ]
                    ],
                    ['$unwind' => '$users_detail'], //flattens collection
                    [
                        '$project' => [
                            "_id" => 0,
                            "email" => 1,
                            "latitude" => 1,
                            "longitude" => 1,
                            "image" => '$users_detail.image_48x48',
                            "name" => '$users_detail.name',
                        ]
                    ]
                ]);
            });

            foreach ($locations as &$location) {
                $location['image'] = url('storage/' . $location['image']);
            }

            $this->setResponse(false);
            $this->_response['data'] = $locations;
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
