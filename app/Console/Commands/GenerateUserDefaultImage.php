<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Facades\CreateDPWithLetter;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class GenerateUserDefaultImage extends Command
{
    private $_count = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:generateImage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate users profile 48x48 image.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tenants = Tenant::all();

        $tenants->each(function ($tenant) {

            $tenant->configure()->use();
            $this->line('');
            $this->line("-----------------------------------------");
            $this->info("Update Users for Tenant #{$tenant->id} ({$tenant->business_name})");
            $this->line("-----------------------------------------");

            $this->_count = 0;

            $users = User::all();

            $users->each(function ($user) {
                if (Storage::disk('public')->exists($user->image)) {
                    $image_resize = Image::make(Storage::disk('public')->get($user->image));
                    $image_resize->resize(48, 48); //before
                    $extension = File::extension($user->image);
                    $fileName = getUniqueStamp() . '_48x48.' . $extension;
                    $image_resize->save(base_path('public' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'user_images' . DIRECTORY_SEPARATOR . '' . $fileName), 60);
                    $user->image_48x48 = "user_images/{$fileName}";
                    $user->save();
                    $this->_count++;
                }
            });

            echo $this->_count . " User Processed.\n";
        });
    }
}
