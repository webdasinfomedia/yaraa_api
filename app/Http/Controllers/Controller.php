<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use App\Facades\CreateDPWithLetter;
use Exception;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Routing\Controller as BaseController;
use Intervention\Image\ImageManagerStatic as Image;

class Controller extends BaseController
{
    protected $_response = ["data" => null, "error" => true, "message" => null];
    protected $_responseCode;
    protected $_files = [];

    protected function setResponse($status = true, $message = null, $responseCode = 200)
    {
        $this->_response["error"] = $status;
        $this->_response["message"] = is_array($message) ?  implode(",", $message) : $message;
        $this->_responseCode = $responseCode;
    }

    protected function registerUser($email)
    {
        if (isUserLimitReached()) {
            throw new Exception("User Limit Reached,Please upgrade plan or delete user to add more.");
        }

        if (isAlreadyTenantSlaveUser($email)) {
            // throw new Exception("User with same name already exists.");
            $user = cloneUser($email, app('tenant')->id);
        } else {
            $user = User::create(["email" => $email]);
            $imageName = 'user_images/' . getUniqueStamp() . '.png';
            $path = 'public/' . $imageName;
            $img = CreateDPWithLetter::create($email);
            Storage::put($path, $img->encode());
            $user->name = strtok($email, '@');

            $image_resize = Image::make(Storage::path($path));
            $image_resize->resize(48, 48); //before 60x60
            $fileFullName = $imageName;
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME)) .  getUniqueStamp() . '_48x48.' .  'png';
            $image_resize->save(base_path('public/storage/user_images/' . $fileName), 60);

            $user->image = $imageName;
            $user->image = $imageName;
            $user->image_48x48 = "user_images/{$fileName}";
            $user->is_verified = false;
            $user->save();

            createTenantSlaveUser($user->email);
        }
        return $user;
    }

    protected function addFileAttachments($attachments, $path = "no-path/attachments/")
    {
        $this->resetFiles();
        $path = Str::finish($path, '/');
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                if (!empty($attachment)) {
                    $filePath = $this->uploadSingleFile($attachment, $path, $key);
                    $this->_files[] = $filePath;
                    // $fileFullName = $attachment->getClientOriginalName();
                    // $fileName = str_replace(' ','_',pathinfo($fileFullName,PATHINFO_FILENAME));
                    // $filePath = $path . $fileName . '-' . getUniqueStamp() . $key . '.' . $attachment->extension();
                    // $attachment->storeAs('public',$filePath);
                }
            }
        }

        return $this->_files;
    }

    private function uploadSingleFile($file,  $path = "no-path/attachments/", $key = null)
    {
        $fileFullName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME));
        $filePath = $path . $fileName . '-' . getUniqueStamp() . $key . '.' . $file->extension();
        $file->storeAs('public', $filePath);
        return $filePath;
    }

    protected function removeFileAttachment(string $fileUrls)
    {
        $filesDeleted = [];
        $deletableFiles = explode(',', $fileUrls);
        if (!empty($deletableFiles)) {
            foreach ($deletableFiles as $fileUrl) {
                $file = $this->removeSingleFile($fileUrl);
                $filesDeleted[] = $file;
                // $file = str_replace(url('storage').'/', '', $fileUrl);
                // if(Storage::disk('public')->delete('public/'.$file) || Storage::delete('public/'.$file)){
                //     $filesDeleted[] = $file;
                // }
            }
        }

        return $filesDeleted;
    }

    private function removeSingleFile(string $url)
    {
        $file = str_replace(url('storage') . '/', '', $url);
        if (Storage::disk('public')->delete('public/' . $file) || Storage::delete('public/' . $file)) {
            return $file;
        }
    }

    protected function resetFiles()
    {
        $this->_files = [];
    }

    protected function uploadFile($file,  $path = "no-path/attachments/")
    {
        $path = Str::finish($path, '/');

        if (!empty($file)) {
            return $this->uploadSingleFile($file, $path);
        }

        return false;
    }

    protected function removeFile($file)
    {
        if ($file) {
            return $this->removeSingleFile($file);
        }

        return false;
    }
}
