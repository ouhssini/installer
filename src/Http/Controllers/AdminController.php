<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SoftCortex\Installer\Services\InstallerService;

class AdminController extends Controller
{
    public function __construct(
        private InstallerService $installer
    ) {}

    public function index()
    {
        return view('installer::admin');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Create admin user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign admin role if Spatie Permission is available
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $adminRole = config('installer.admin.role', 'admin');
                $createRoleIfMissing = config('installer.admin.create_role_if_missing', true);

                try {
                    $role = \Spatie\Permission\Models\Role::findByName($adminRole);
                } catch (\Exception $e) {
                    if ($createRoleIfMissing) {
                        $role = \Spatie\Permission\Models\Role::create(['name' => $adminRole]);
                    } else {
                        throw $e;
                    }
                }

                // Assign role to user
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }

            $this->installer->completeStep(5);
            $this->installer->setCurrentStep(6);

            return redirect()->route('installer.finalize');

        } catch (\Exception $e) {
            Log::error('Admin user creation failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'Failed to create admin user: ' . $e->getMessage(),
                ]);
        }
    }
}
