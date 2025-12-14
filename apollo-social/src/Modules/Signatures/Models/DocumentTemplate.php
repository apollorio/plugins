<?php

/**
 * Document Template Model
 *
 * @package Apollo\Modules\Signatures\Models
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

namespace Apollo\Modules\Signatures\Models;

/**
 * Document Template Model
 *
 * Representa um modelo de documento com placeholders para geração de PDFs
 *
 * @since 1.0.0
 */
class DocumentTemplate
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var string HTML/Markdown content with placeholders */
    public $content;

    /** @var array Available placeholders */
    public $placeholders;

    /** @var string Template category */
    public $category;

    /** @var bool */
    public $is_active;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    /** @var int User who created */
    public $created_by;

    /**
     * Constructor
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Ensure placeholders is array
        if (is_string($this->placeholders)) {
            $this->placeholders = json_decode($this->placeholders, true) ?: [];
        }
    }

    /**
     * Get placeholders from content
     *
     * @return array
     */
    public function extractPlaceholders(): array
    {
        if (empty($this->content)) {
            return [];
        }

        // Extract {{placeholder}} patterns
        preg_match_all('/\{\{([^}]+)\}\}/', $this->content, $matches);

        $placeholders = [];
        foreach ($matches[1] as $placeholder) {
            $key                  = trim($placeholder);
            $placeholders[ $key ] = [
                'key'      => $key,
                'label'    => ucfirst(str_replace('_', ' ', $key)),
                'type'     => $this->guessPlaceholderType($key),
                'required' => true,
            ];
        }

        return $placeholders;
    }

    /**
     * Guess placeholder type based on key name
     *
     * @param string $key
     * @return string
     */
    private function guessPlaceholderType(string $key): string
    {
        $key = strtolower($key);

        if (strpos($key, 'email') !== false) {
            return 'email';
        }
        if (strpos($key, 'phone') !== false || strpos($key, 'telefone') !== false) {
            return 'tel';
        }
        if (strpos($key, 'date') !== false || strpos($key, 'data') !== false) {
            return 'date';
        }
        if (strpos($key, 'cpf') !== false || strpos($key, 'cnpj') !== false) {
            return 'text';
        }
        if (strpos($key, 'value') !== false || strpos($key, 'valor') !== false || strpos($key, 'price') !== false) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Render template with data
     *
     * @param array $data
     * @return string
     */
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content     = str_replace($placeholder, (string) $value, $content);
        }

        return $content;
    }

    /**
     * Validate required placeholders are provided
     *
     * @param array $data
     * @return array Validation errors
     */
    public function validateData(array $data): array
    {
        $errors       = [];
        $placeholders = $this->extractPlaceholders();

        foreach ($placeholders as $key => $config) {
            if ($config['required'] && (! isset($data[ $key ]) || empty($data[ $key ]))) {
                $errors[ $key ] = "O campo {$config['label']} é obrigatório";
            }
        }

        return $errors;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'content'      => $this->content,
            'placeholders' => $this->placeholders,
            'category'     => $this->category,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'created_by'   => $this->created_by,
        ];
    }
}
