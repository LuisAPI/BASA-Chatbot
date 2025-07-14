<?php

namespace App\Http\Controllers;

use App\Models\UserFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileManagementController extends Controller
{
    /**
     * Show the file management dashboard.
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Get user's own files
        $ownFiles = $user->files()->orderBy('created_at', 'desc')->get();
        
        // Get files shared with this user
        $sharedFiles = $user->sharedFiles()->orderBy('created_at', 'desc')->get();
        
        // Get all users for sharing dropdown
        $allUsers = User::where('id', '!=', $user->id)->get(['id', 'name', 'email']);
        
        return view('file-management', compact('ownFiles', 'sharedFiles', 'allUsers'));
    }

    /**
     * Update file sharing settings.
     */
    public function updateSharing(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'file_id' => 'required|exists:user_files,id',
            'is_public' => 'boolean',
            'shared_with' => 'array',
            'shared_with.*' => 'exists:users,id'
        ]);

        $file = UserFile::findOrFail($request->file_id);
        
        // Check if user owns this file
        if ($file->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file->update([
            'is_public' => $request->boolean('is_public', false),
            'shared_with_users' => $request->input('shared_with', [])
        ]);

        return response()->json([
            'message' => 'File sharing updated successfully',
            'file' => $file->fresh()
        ]);
    }

    /**
     * Delete a file.
     */
    public function delete(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'file_id' => 'required|exists:user_files,id'
        ]);

        $file = UserFile::findOrFail($request->file_id);
        
        // Check if user owns this file
        if ($file->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Delete physical file
            if (Storage::disk('local')->exists($file->storage_path)) {
                Storage::disk('local')->delete($file->storage_path);
            }

            // Delete RAG chunks
            \Illuminate\Support\Facades\DB::table('rag_chunks')
                ->where('source', $file->original_name)
                ->where('user_id', $file->user_id)
                ->delete();

            // Delete file record
            $file->delete();

            return response()->json(['message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get file details for sharing modal.
     */
    public function getFileDetails(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'file_id' => 'required|exists:user_files,id'
        ]);

        $file = UserFile::with('user')->findOrFail($request->file_id);
        
        // Check if user can access this file
        if (!$file->canBeAccessedBy(Auth::user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'file' => $file,
            'can_edit' => $file->user_id === Auth::id()
        ]);
    }

    /**
     * Get users for sharing dropdown.
     */
    public function getUsers(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $users = User::where('id', '!=', Auth::id())
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json(['users' => $users]);
    }
} 