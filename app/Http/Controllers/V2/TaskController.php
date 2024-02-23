<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function csvImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $file = $request->csv_file;
            $path = "task/csv/";
            $fileFullName = $file->getClientOriginalName();
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME));
            $filePath = $path . $fileName . '-' . getUniqueStamp() . '.csv';
            $file->storeAs('public', $filePath);

            //import csv
            $csvFile = $filePath;

            if (Storage::disk('public')->exists($csvFile)) {
                $csvFile = Storage::disk('public')->path($csvFile);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $task = Task::create([
                        'project_id' => $data[0],
                        'name' => $data[1],
                        'description' => $data[2],
                        'visibility' => $data[3],
                        'recurrence' => "no",
                        'created_by' => Auth::user()->id,
                        'assigned_by' => Auth::user()->email,
                        'reminder' => false,
                        'status' => "pending",
                        // 'priority' => $data[7],
                        'priority' => "high",
                    ]);

                    // Check if project is exists or not
                    $validation = Validator::make(['project_id' => $task->project_id], [
                        'project_id' => 'filled|exists:projects,_id',
                    ]);
                    if ($validation->fails()) {
                        $task->project_id = null;
                    }

                    // For start_date
                    $start_date = $data[4];
                    if ($start_date) {
                        $task->start_date = $start_date;
                    } else {
                        $task->start_date = null;
                    }

                    // For due_date
                    $end_date = $data[5];
                    if ($end_date) {
                        $task->due_date = $end_date;
                    } else {
                        $currentDate = Carbon::now();
                        $dueDate = $currentDate->addDay();
                        $formattedDueDate = $dueDate->format('Y-m-d h:i A');
                        $date = Carbon::createFromFormat('Y-m-d H:i A', $formattedDueDate, getUserTimezone());
                        $date->setTimezone('UTC');
                        $task->due_date = $date;
                    }

                    //add assign members to task
                    $assignee = array_filter(explode(',', $data[6]));
                    $assignee[] = auth()->user()->email; //keep task owner as member
                    $task->sync($assignee, [], 'assignedTo')->syncProjectMember();
                    $task->skipUser(auth()->id())->sendTaskMail(false);

                    $task->save();
                }

                Storage::disk('public')->delete($csvFile);
            }

            $this->setResponse(false, "Tasks imported successfully");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
