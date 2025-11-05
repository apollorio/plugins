<?php
namespace Apollo\Domain\Entities;

/**
 * Group Entity
 */
class GroupEntity
{
    public $id;
    public $title;
    public $slug;
    public $type;
    public $season_slug;
    public $description;
    public $status;
    public $members_count = 0;
    public $created_by;
    public $created_at;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->type = $data['type'] ?? 'comunidade';
        $this->season_slug = $data['season_slug'] ?? null;
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->members_count = $data['members_count'] ?? 0;
        $this->created_by = $data['created_by'] ?? 0;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
    
    public function isSeason()
    {
        return $this->type === 'season';
    }
    
    public function isNucleo()
    {
        return $this->type === 'nucleo';
    }
    
    public function isComunidade()
    {
        return $this->type === 'comunidade';
    }
}