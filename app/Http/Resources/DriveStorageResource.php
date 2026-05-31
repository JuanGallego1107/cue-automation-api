<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriveStorageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'drive_file_id'   => $this->drive_file_id,
            'drive_folder_id' => $this->drive_folder_id,
            'drive_url'       => $this->drive_url,
            'drive_filename'  => $this->drive_filename,
            'folder_path'     => $this->folder_path,
            'file_size_bytes' => $this->file_size_bytes,
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
