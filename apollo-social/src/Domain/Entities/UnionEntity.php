<?php
namespace Apollo\Domain\Entities;

/**
 * Union Entity
 */
class UnionEntity
{
    public $id;
    public $title;
    public $slug;
    public $description;
    public $badges_toggle = true;
    public $members_count = 0;
    public $managers = [];
    public $created_at;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->badges_toggle = $data['badges_toggle'] ?? true;
        $this->members_count = $data['members_count'] ?? 0;
        $this->managers = $data['managers'] ?? [];
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
    
    public function hasManager($user_id)
    {
        return in_array($user_id, $this->managers);
    }
}