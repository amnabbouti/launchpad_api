<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttachmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'file_type' => $this->file_type,
            'file_type_name' => $this->file_type_name,
            'extension' => $this->extension,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'file_path' => $this->file_path,
            'description' => $this->description,
            'category' => $this->category,
            'url' => $this->url,
            'user_id' => $this->user_id,

            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
        ];

        return $this->addCommonData($data, $request);
    }
}
