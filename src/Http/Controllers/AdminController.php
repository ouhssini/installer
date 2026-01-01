<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            $userModel = config('auth.providers.users.model', 'App\\Models\\User');

            // Create admin user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Smart role assignment
            $this->assignAdminRole($userId, $userModel);

            // Store admin user ID for auto-login
            $this->installer->setSetting('admin_user_id', $userId);

            $this->installer->completeStep(6);
            $this->installer->setCurrentStep(7);

            return redirect()->route('installer.finalize');

        } catch (\Exception $e) {
            Log::error('Admin user creation failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'Failed to create admin user: '.$e->getMessage(),
                ]);
        }
    }

    /**
     * Smart role assignment - checks for roles column or Spatie traits
     */
    private function assignAdminRole(int $userId, string $userModel): void
    {
        $adminRole = config('installer.admin.role', 'admin');

        try {
            // Check if users table has a 'role' or 'roles' column
            if (DB::getSchemaBuilder()->hasColumn('users', 'role')) {
                DB::table('users')->where('id', $userId)->update(['role' => $adminRole]);
                Log::info('Assigned role via role column', ['user_id' => $userId, 'role' => $adminRole]);

                return;
            }

            if (DB::getSchemaBuilder()->hasColumn('users', 'roles')) {
                DB::table('users')->where('id', $userId)->update(['roles' => $adminRole]);
                Log::info('Assigned role via roles column', ['user_id' => $userId, 'role' => $adminRole]);

                return;
            }

            // Check if User model uses Spatie HasRoles trait
            if (class_exists($userModel)) {
                $reflection = new \ReflectionClass($userModel);
                $traits = $reflection->getTraitNames();

                if (in_array('Spatie\\Permission\\Traits\\HasRoles', $traits)) {
                    // User model has HasRoles trait - use Spatie
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $createRoleIfMissing = config('installer.admin.create_role_if_missing', true);

                        try {
                            $role = \Spatie\Permission\Models\Role::findByName($adminRole);
                        } catch (\Exception $e) {
                            if ($createRoleIfMissing) {
                                $role = \Spatie\Permission\Models\Role::create(['name' => $adminRole]);
                                Log::info('Created Spatie role', ['role' => $adminRole]);
                            } else {
                                throw $e;
                            }
                        }

                        // Assign role to user via model_has_roles table
                        DB::table('model_has_roles')->insert([
                            'role_id' => $role->id,
                            'model_type' => $userModel,
                            'model_id' => $userId,
                        ]);

                        Log::info('Assigned role via Spatie', ['user_id' => $userId, 'role' => $adminRole]);

                        return;
                    }
                }
            }

            // No role system detected - log warning
            Log::warning('No role system detected. User created without role assignment.', [
                'user_id' => $userId,
                'expected_role' => $adminRole,
            ]);

        } catch (\Exception $e) {
            Log::error('Role assignment failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - user is created, just without role
        }
    }
}
