<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobArtworkFile;
use App\Models\JobCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobArtworkController extends Controller
{
    public function index(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);
        return response()->json($jobCard->artworkFiles()->with('uploadedBy:id,name')->get());
    }

    public function store(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $request->validate([
            'file'  => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,pdf,ai,psd,eps,svg,tiff,tif',
            'notes' => 'nullable|string|max:500',
        ]);

        $file      = $request->file('file');
        $original  = $file->getClientOriginalName();
        $ext       = $file->getClientOriginalExtension();
        $stored    = Str::uuid() . '.' . $ext;
        $path      = "artwork/{$jobCard->id}/{$stored}";

        Storage::disk('public')->putFileAs("artwork/{$jobCard->id}", $file, $stored);

        $version = JobArtworkFile::where('job_card_id', $jobCard->id)->max('version') + 1;

        $artwork = JobArtworkFile::create([
            'job_card_id'   => $jobCard->id,
            'branch_id'     => $jobCard->branch_id,
            'original_name' => $original,
            'stored_name'   => $stored,
            'file_path'     => $path,
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'version'       => $version,
            'notes'         => $request->input('notes'),
            'uploaded_by'   => $request->user()->id,
        ]);

        return response()->json($artwork->load('uploadedBy:id,name'), 201);
    }

    public function destroy(JobArtworkFile $jobArtworkFile)
    {
        $this->authorizeBranch($jobArtworkFile->branch_id);
        Storage::disk('public')->delete($jobArtworkFile->file_path);
        $jobArtworkFile->delete();
        return response()->json(['message' => 'File deleted']);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
