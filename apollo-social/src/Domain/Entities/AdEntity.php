<?php
namespace Apollo\Domain\Entities;

/**
 * Ad Entity (Classified)
 */
class AdEntity
{
    public $id;
    public $title;
    public $slug;
    public $description;
    public $price;
    public $category;
    public $season_slug;
    public $group_id;
    public $author_id;
    public $status;
    public $created_at;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->price = $data['price'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->season_slug = $data['season_slug'] ?? null;
        $this->group_id = $data['group_id'] ?? null;
        $this->author_id = $data['author_id'] ?? 0;
        $this->status = $data['status'] ?? 'active';
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
    
    public function isInSeason()
    {
        return !empty($this->season_slug);
    }
}