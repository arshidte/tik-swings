<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key', 'value', 'group'];
    protected $useTimestamps    = true;

    /**
     * Return all settings as a flat key => value map (cached).
     */
    public function allAsMap(): array
    {
        return cache()->remember('settings_map', 3600, function () {
            $rows = $this->findAll();
            $map  = [];
            foreach ($rows as $row) {
                $map[$row['key']] = $row['value'];
            }

            return $map;
        });
    }

    public function put(string $key, ?string $value, string $group = 'general'): void
    {
        $existing = $this->where('key', $key)->first();
        if ($existing) {
            $this->update($existing['id'], ['value' => $value, 'group' => $group]);
        } else {
            $this->insert(['key' => $key, 'value' => $value, 'group' => $group]);
        }
        cache()->delete('settings_map');
    }
}
