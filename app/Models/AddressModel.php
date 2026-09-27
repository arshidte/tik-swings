<?php

namespace App\Models;

use CodeIgniter\Model;

class AddressModel extends Model
{
    protected $table            = 'addresses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'user_id', 'label', 'full_name', 'phone', 'line1', 'line2',
        'city', 'state', 'pincode', 'country', 'is_default',
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'full_name' => 'required|max_length[120]',
        'phone'     => 'required|min_length[10]|max_length[20]',
        'line1'     => 'required|max_length[190]',
        'city'      => 'required|max_length[80]',
        'state'     => 'required|max_length[80]',
        'pincode'   => 'required|min_length[4]|max_length[12]',
    ];

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function makeDefault(int $userId, int $addressId): void
    {
        $this->where('user_id', $userId)->set(['is_default' => 0])->update();
        $this->update($addressId, ['is_default' => 1]);
    }
}
