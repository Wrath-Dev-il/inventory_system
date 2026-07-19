<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserManagementController extends Controller
{
    private array $roleMap = [
        1 => 'Admin',
        2 => 'Employee 1',
        3 => 'Employee 2',
    ];

    public function index()
    {
        $stats = $this->userStats();
        $role = (int) (request('role', 1));
        $users = $this->paginatedUsers($role, request());

        return view('admin.system-security.user-management', [
            'title' => 'User Management',
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
            'stats' => $stats,
            'users' => $users,
            'currentRole' => $role,
            'searches' => request('search', []),
            'fetchUrl' => route('admin.system-security.user-management.fetch', ['role' => '__ROLE__']),
            'storeUrl' => route('admin.system-security.user-management.store'),
            'updateUrlTemplate' => route('admin.system-security.user-management.update', ['login' => '__USER_ID__']),
            'destroyUrlTemplate' => route('admin.system-security.user-management.destroy', ['login' => '__USER_ID__']),
            'roles' => $this->roleMap,
        ]);
    }

    public function fetch(Request $request, int $role): JsonResponse
    {
        if (!isset($this->roleMap[$role])) {
            return response()->json(['message' => 'Invalid role.'], 422);
        }

        $users = $this->paginatedUsers($role, $request);

        $normalized = array_map(fn ($u) => $this->normalizeUser($u), $users->items());

        return response()->json([
            'users' => $normalized,
            'pagination' => $this->paginationMeta($users),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless((int) $request->user()?->account_type === 1, 403);

        $validated = $request->validate([
            'User_ID' => 'required|string|max:255|unique:logins,User_ID',
            'Password' => 'required|string|min:4|max:255',
            'account_type' => 'required|integer|in:1,2,3',
            'access_modules' => 'nullable|string',
            'User_First_Name' => 'nullable|string|max:255',
            'User_Middle_Name' => 'nullable|string|max:255',
            'User_Last_Name' => 'nullable|string|max:255',
            'Gender' => 'nullable|string|max:255',
            'Email' => 'nullable|email|max:255',
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                return Login::create([
                    'account_type' => $validated['account_type'],
                    'User_ID' => $validated['User_ID'],
                    'Password' => Hash::make($validated['Password']),
                    'access_modules' => $validated['access_modules'] ?? null,
                    'User_First_Name' => $validated['User_First_Name'] ?? '',
                    'User_Middle_Name' => $validated['User_Middle_Name'] ?? null,
                    'User_Last_Name' => $validated['User_Last_Name'] ?? '',
                    'Gender' => $validated['Gender'] ?? '',
                    'Email' => $validated['Email'] ?? null,
                ]);
            });

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $this->normalizeUser($user),
                'stats' => $this->userStats(),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Login $login): JsonResponse
    {
        abort_unless((int) $request->user()?->account_type === 1, 403);

        $validated = $request->validate([
            'User_ID' => 'required|string|max:255|unique:logins,User_ID,' . $login->login_ID . ',login_ID',
            'Password' => 'nullable|string|min:4|max:255',
            'account_type' => 'required|integer|in:1,2,3',
            'access_modules' => 'nullable|string',
            'User_First_Name' => 'nullable|string|max:255',
            'User_Middle_Name' => 'nullable|string|max:255',
            'User_Last_Name' => 'nullable|string|max:255',
            'Gender' => 'nullable|string|max:255',
            'Email' => 'nullable|email|max:255',
        ]);

        try {
            DB::transaction(function () use ($login, $validated) {
                $data = [
                    'account_type' => $validated['account_type'],
                    'User_ID' => $validated['User_ID'],
                    'access_modules' => $validated['access_modules'] ?? $login->access_modules,
                    'User_First_Name' => $validated['User_First_Name'] ?? $login->User_First_Name,
                    'User_Middle_Name' => $validated['User_Middle_Name'] ?? $login->User_Middle_Name,
                    'User_Last_Name' => $validated['User_Last_Name'] ?? $login->User_Last_Name,
                    'Gender' => $validated['Gender'] ?? $login->Gender,
                    'Email' => $validated['Email'] ?? $login->Email,
                ];

                if (filled($validated['Password'])) {
                    $data['Password'] = Hash::make($validated['Password']);
                }

                $login->update($data);
            });

            $login->refresh();

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $this->normalizeUser($login),
                'stats' => $this->userStats(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Login $login): JsonResponse
    {
        abort_unless((int) $request->user()?->account_type === 1, 403);

        if ((int) $login->login_ID === (int) $request->user()->login_ID) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        try {
            $login->delete();

            return response()->json([
                'message' => 'User deleted successfully.',
                'stats' => $this->userStats(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }

    private function paginatedUsers(int $role, Request $request): LengthAwarePaginator
    {
        $perPage = 25;
        $page = (int) $request->query('page', 1);
        $searches = $request->query('search', []);

        $query = Login::where('account_type', $role);

        if (!empty($searches)) {
            foreach ($searches as $column => $value) {
                if (filled($value)) {
                    $query->where($column, 'like', '%' . $value . '%');
                }
            }
        }

        $query->orderBy('login_ID', 'asc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function userStats(): array
    {
        return [
            'admin' => Login::where('account_type', 1)->count(),
            'employee_1' => Login::where('account_type', 2)->count(),
            'employee_2' => Login::where('account_type', 3)->count(),
            'total' => Login::whereIn('account_type', [1, 2, 3])->count(),
        ];
    }

    private function normalizeUser(Login $user): array
    {
        return [
            'login_ID' => $user->login_ID,
            'User_ID' => $user->User_ID,
            'account_type' => (int) $user->account_type,
            'role_label' => $this->roleMap[(int) $user->account_type] ?? 'Unknown',
            'access_modules' => $user->access_modules,
            'User_First_Name' => $user->User_First_Name,
            'User_Middle_Name' => $user->User_Middle_Name,
            'User_Last_Name' => $user->User_Last_Name,
            'Gender' => $user->Gender,
            'Email' => $user->Email,
            'display_name' => $user->display_name,
        ];
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
