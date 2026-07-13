<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkspaceGroup extends Model
{
    protected $fillable = ['uuid', 'name', 'color'];

    protected static function booted()
    {
        static::creating(fn($group) => $group->uuid = Str::uuid()->toString());
    }

    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_group_members')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('workspace_group_members.sort_order');
    }

    public function addWorkspaces(array $workspaceIds)
    {
        $current = $this->workspaces()->pluck('workspaces.id')->toArray();
        $toAdd = array_diff($workspaceIds, $current);

        foreach ($toAdd as $index => $id) {
            $this->workspaces()->attach($id, ['sort_order' => $index]);
        }
    }

    public function removeWorkspaces(array $workspaceIds)
    {
        $this->workspaces()->detach($workspaceIds);
    }
}
