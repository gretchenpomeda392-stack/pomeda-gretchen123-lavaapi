<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primary_key = 'id';

    protected $fillable = [
        'product_name',
        'description',
        'price',
        'quantity',
        'created_at'
    ];

    protected $guarded = ['id'];

    public function __construct()
    {
        parent::__construct();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function getAll()
    {
        $stmt = $this->db->raw(
            "SELECT id,
                    product_name,
                    description,
                    price,
                    quantity,
                    created_at
             FROM products
             ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT BY ID
    |--------------------------------------------------------------------------
    */

    public function getById($id)
    {
        $stmt = $this->db->raw(
            "SELECT id,
                    product_name,
                    description,
                    price,
                    quantity,
                    created_at
             FROM products
             WHERE id = ?
             LIMIT 1",
            [$id]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function create($data)
    {
        return $this->insert($data);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function updateProduct($id, $data)
    {
        $stmt = $this->db->raw(
            "UPDATE products
             SET product_name = ?,
                 description = ?,
                 price = ?,
                 quantity = ?
             WHERE id = ?",
            [
                $data['product_name'],
                $data['description'],
                $data['price'],
                $data['quantity'],
                $id
            ]
        );

        return $stmt->rowCount();
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function deleteProduct($id)
    {
        $stmt = $this->db->raw(
            "DELETE FROM products
             WHERE id = ?",
            [$id]
        );

        return $stmt->rowCount();
    }
}